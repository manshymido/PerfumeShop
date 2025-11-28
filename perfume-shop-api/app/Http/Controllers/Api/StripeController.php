<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CartCalculationService;
use App\Services\InventoryService;
use App\Services\StripeService;
use App\Traits\ApiResponse;
use App\Traits\CartOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    use ApiResponse, CartOwnership;

    protected StripeService $stripeService;
    protected InventoryService $inventoryService;
    protected CartCalculationService $cartCalculationService;

    public function __construct(
        StripeService $stripeService,
        InventoryService $inventoryService,
        CartCalculationService $cartCalculationService
    ) {
        $this->stripeService = $stripeService;
        $this->inventoryService = $inventoryService;
        $this->cartCalculationService = $cartCalculationService;
    }

    /**
     * Validate cart and stock before checkout.
     */
    public function validateCheckout(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-ID');

        if (!$user && !$sessionId) {
            return $this->errorResponse('Cart is empty or session required', null, 400);
        }

        // Get cart items using trait method
        $cartItems = $this->getCartItemsWithSessionFallback($request, ['product.inventory']);

        if ($cartItems->isEmpty()) {
            return $this->errorResponse('Cart is empty', null, 400);
        }

        // Validate stock
        $stockErrors = $this->validateCartStock($cartItems);
        if (!empty($stockErrors)) {
            return $this->errorResponse('Stock validation failed', $stockErrors, 422);
        }

        // Calculate totals using service
        $totals = $this->cartCalculationService->calculateTotals($cartItems);

        return $this->successResponse([
            ...$totals,
            'items_count' => $cartItems->count(),
        ]);
    }

    /**
     * Create Stripe payment intent.
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-ID');

        $request->validate([
            'shipping_address_id' => ['required', 'exists:shipping_addresses,id'],
        ]);

        // Verify shipping address belongs to user
        $shippingAddress = $user->shippingAddresses()->findOrFail($request->shipping_address_id);

        // Get cart items using trait method
        $cartItems = $this->getCartItemsWithSessionFallback($request, ['product.inventory']);

        if ($cartItems->isEmpty()) {
            return $this->errorResponse('Cart is empty', null, 400);
        }

        // Validate stock
        $stockErrors = $this->validateCartStock($cartItems);
        if (!empty($stockErrors)) {
            return $this->errorResponse('Stock validation failed', $stockErrors, 422);
        }

        // Calculate totals using service
        $totals = $this->cartCalculationService->calculateTotals($cartItems);

        try {
            $paymentIntent = $this->stripeService->createPaymentIntent(
                $totals['total'],
                'usd',
                [
                    'user_id' => $user->id ?? 'guest',
                    'session_id' => $sessionId ?? null,
                ]
            );

            return $this->successResponse([
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $totals['total'],
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe payment intent creation failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to create payment intent', null, 500);
        }
    }

    /**
     * Update Stripe payment intent amount.
     */
    public function updatePaymentIntent(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-ID');

        $request->validate([
            'payment_intent_id' => ['required', 'string'],
            'shipping_address_id' => ['required', 'exists:shipping_addresses,id'],
        ]);

        // Verify shipping address belongs to user
        $shippingAddress = $user->shippingAddresses()->findOrFail($request->shipping_address_id);

        // Get cart items using trait method
        $cartItems = $this->getCartItemsWithSessionFallback($request, ['product.inventory']);

        if ($cartItems->isEmpty()) {
            return $this->errorResponse('Cart is empty', null, 400);
        }

        // Calculate totals using service
        $totals = $this->cartCalculationService->calculateTotals($cartItems);

        try {
            // Retrieve existing payment intent
            $paymentIntent = $this->stripeService->retrievePaymentIntent($request->payment_intent_id);

            // Check if payment intent is in a state that can be updated
            if (!in_array($paymentIntent->status, ['requires_payment_method', 'requires_confirmation'])) {
                return $this->errorResponse('Payment intent cannot be updated', null, 400);
            }

            // Update payment intent amount
            $updatedPaymentIntent = $this->stripeService->updatePaymentIntent(
                $request->payment_intent_id,
                $totals['total'],
                [
                    'user_id' => $user->id ?? 'guest',
                    'session_id' => $sessionId ?? null,
                ]
            );

            return $this->successResponse([
                'client_secret' => $updatedPaymentIntent->client_secret,
                'payment_intent_id' => $updatedPaymentIntent->id,
                'amount' => $totals['total'],
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe payment intent update failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to update payment intent', null, 500);
        }
    }

    /**
     * Handle Stripe webhooks.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        $event = $this->stripeService->verifyWebhookSignature($payload, $signature);

        if (!$event) {
            return $this->errorResponse('Invalid webhook signature', null, 400);
        }

        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleRefund($event->data->object);
                    break;
            }

            return $this->successResponse(null);
        } catch (\Exception $e) {
            Log::error('Webhook handling failed: ' . $e->getMessage());
            return $this->errorResponse('Webhook processing failed', null, 500);
        }
    }

    /**
     * Handle successful payment (backup verification).
     * Primary handling is done by OrderController::store()
     */
    protected function handlePaymentSucceeded($paymentIntent): void
    {
        // Verify order exists - this is backup verification
        $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        
        if ($order) {
            Log::info('Payment verified via webhook', [
                'payment_intent_id' => $paymentIntent->id,
                'order_id' => $order->id,
                'status' => $order->status,
            ]);
        } else {
            Log::warning('Payment succeeded but no order found', [
                'payment_intent_id' => $paymentIntent->id,
            ]);
        }
    }

    /**
     * Handle failed payment.
     */
    protected function handlePaymentFailed($paymentIntent): void
    {
        Log::warning('Payment failed via webhook', [
            'payment_intent_id' => $paymentIntent->id,
            'status' => $paymentIntent->status,
        ]);
    }

    /**
     * Validate cart stock and return errors if any.
     *
     * @param \Illuminate\Support\Collection $cartItems
     * @return array
     */
    protected function validateCartStock($cartItems): array
    {
        $items = $cartItems->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ];
        })->toArray();

        return $this->inventoryService->validateStock($items);
    }

    /**
     * Handle refund.
     */
    protected function handleRefund($charge): void
    {
        $order = Order::where('stripe_payment_intent_id', $charge->payment_intent)->first();

        if ($order && $order->status !== 'refunded') {
            DB::transaction(function () use ($order) {
                $order->status = 'refunded';
                $order->save();

                // Create status history
                $order->statusHistory()->create([
                    'status' => 'refunded',
                    'notes' => 'Refund processed via Stripe',
                    'updated_by' => 'system',
                ]);

                // Increase inventory back
                $this->inventoryService->increaseInventoryForOrder($order);
            });
        }
    }
}
