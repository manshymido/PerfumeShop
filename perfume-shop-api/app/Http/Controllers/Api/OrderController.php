<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Jobs\SendOrderConfirmationEmail;
use App\Jobs\SendOrderStatusUpdateEmail;
use App\Services\CartCalculationService;
use App\Services\InventoryService;
use App\Services\StripeService;
use App\Traits\ApiResponse;
use App\Traits\CartOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    use ApiResponse, CartOwnership;

    protected InventoryService $inventoryService;
    protected StripeService $stripeService;
    protected CartCalculationService $cartCalculationService;

    public function __construct(
        InventoryService $inventoryService,
        StripeService $stripeService,
        CartCalculationService $cartCalculationService
    ) {
        $this->inventoryService = $inventoryService;
        $this->stripeService = $stripeService;
        $this->cartCalculationService = $cartCalculationService;
    }

    /**
     * Display a listing of user's orders.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['orderItems.product', 'shippingAddress', 'statusHistory'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return $this->paginatedResponse(
            $orders,
            OrderResource::collection($orders->items())
        );
    }

    /**
     * Store a newly created order (after payment confirmation).
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-ID');

        // Verify payment intent
        try {
            $paymentIntent = $this->stripeService->retrievePaymentIntent($request->payment_intent_id);

            if ($paymentIntent->status !== 'succeeded') {
                return $this->errorResponse('Payment not completed', null, 400);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid payment intent', null, 400);
        }

        // Check if order already exists
        $existingOrder = Order::where('stripe_payment_intent_id', $request->payment_intent_id)->first();
        if ($existingOrder) {
            return $this->successResponse(
                new OrderResource($existingOrder->load(['orderItems.product', 'shippingAddress', 'statusHistory'])),
                'Order already exists'
            );
        }

        // Verify shipping address belongs to user
        $shippingAddress = $user->shippingAddresses()->findOrFail($request->shipping_address_id);

        // Get cart items using trait method
        $cartItems = $this->getCartItemsWithSessionFallback($request, ['product.inventory']);

        if ($cartItems->isEmpty()) {
            return $this->errorResponse('Cart is empty', null, 400);
        }

        // Calculate totals using service
        $totals = $this->cartCalculationService->calculateTotals($cartItems);
        $subtotal = $totals['subtotal'];
        $tax = $totals['tax'];
        $shippingCost = $totals['shippingCost'];
        $total = $totals['total'];

        // Verify payment intent amount matches cart total
        $paymentIntentAmount = $paymentIntent->amount / 100; // Convert from cents to dollars
        $amountDifference = abs($paymentIntentAmount - $total);
        
        // Allow small rounding differences (less than 1 cent)
        if ($amountDifference > 0.01) {
            Log::warning('Payment intent amount mismatch', [
                'payment_intent_id' => $request->payment_intent_id,
                'payment_intent_amount' => $paymentIntentAmount,
                'cart_total' => $total,
                'difference' => $amountDifference,
            ]);
            
            return $this->errorResponse(
                'Payment amount mismatch. Please refresh and try again.',
                [
                    'payment_intent_amount' => $paymentIntentAmount,
                    'cart_total' => $total,
                ],
                400
            );
        }

        return DB::transaction(function () use ($user, $shippingAddress, $cartItems, $subtotal, $tax, $shippingCost, $total, $request) {
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'shipping_address_id' => $shippingAddress->id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'status' => 'pending',
                'stripe_payment_intent_id' => $request->payment_intent_id,
            ]);

            // Create order items
            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->product->price,
                ]);
            }

            // Refresh order to load orderItems relationship
            $order->refresh();
            $order->load('orderItems');

            // Create initial status history
            $order->statusHistory()->create([
                'status' => 'pending',
                'notes' => 'Order created',
                'updated_by' => $user->id,
            ]);

            // Decrease inventory - must be called after orderItems are loaded
            try {
                $this->inventoryService->decreaseInventoryForOrder($order);
            } catch (\Exception $e) {
                Log::error('Inventory decrease failed: ' . $e->getMessage());
                throw $e;
            }

            // Clear cart using trait method
            $this->clearCartWithSessionFallback($request);

            // Update order status to processing
            $order->status = 'processing';
            $order->save();

            $order->statusHistory()->create([
                'status' => 'processing',
                'notes' => 'Payment confirmed, order processing',
                'updated_by' => 'system',
            ]);

            // Send order confirmation email
            SendOrderConfirmationEmail::dispatch($order->fresh());

            return $this->successResponse(
                new OrderResource($order->load(['orderItems.product', 'shippingAddress', 'statusHistory'])),
                'Order created successfully',
                201
            );
        });
    }

    /**
     * Display the specified order.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $order = Order::with(['orderItems.product.images', 'shippingAddress', 'statusHistory'])
            ->findOrFail($id);

        $this->authorize('view', $order);

        return $this->successResponse(new OrderResource($order));
    }

    /**
     * Cancel order (only if pending or processing).
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $this->authorize('cancel', $order);

        if (!$order->canBeCancelled()) {
            return $this->errorResponse('Order cannot be cancelled. Current status: ' . $order->status, null, 400);
        }

        return DB::transaction(function () use ($order, $request) {
            $order->status = 'cancelled';
            $order->save();

            // Create status history
            $order->statusHistory()->create([
                'status' => 'cancelled',
                'notes' => 'Order cancelled by user',
                'updated_by' => $request->user()->id,
            ]);

            // Increase inventory back
            $this->inventoryService->increaseInventoryForOrder($order);

            return $this->successResponse(
                new OrderResource($order->load(['orderItems.product', 'shippingAddress', 'statusHistory'])),
                'Order cancelled successfully'
            );
        });
    }

    /**
     * Generate invoice PDF (placeholder - would need PDF library).
     */
    public function invoice(Request $request, string $id): JsonResponse
    {
        $order = Order::with(['orderItems.product', 'shippingAddress', 'user'])
            ->findOrFail($id);

        $this->authorize('view', $order);

        // In production, generate PDF using a library like dompdf or snappy
        // For now, return order data that can be used to generate invoice
        return $this->successResponse(
            [
                'order' => $order,
                'invoice_number' => 'INV-' . str_pad($order->id, 8, '0', STR_PAD_LEFT),
                'invoice_date' => $order->created_at->format('Y-m-d'),
            ],
            'Invoice data retrieved'
        );
    }
}
