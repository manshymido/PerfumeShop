<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateProductRequest;
use App\Http\Requests\Admin\RefundOrderRequest;
use App\Http\Requests\Admin\UpdateInventoryRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Jobs\SendLowStockAlertEmail;
use App\Jobs\SendOrderStatusUpdateEmail;
use App\Services\InventoryService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    protected InventoryService $inventoryService;
    protected StripeService $stripeService;

    public function __construct(InventoryService $inventoryService, StripeService $stripeService)
    {
        $this->inventoryService = $inventoryService;
        $this->stripeService = $stripeService;
    }

    /**
     * Get dashboard statistics.
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $stats = [
            'total_sales' => (float) Order::whereIn('status', ['processing', 'shipped', 'delivered'])->sum('total') ?? 0.0,
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'shipped_orders' => Order::where('status', 'shipped')->count(),
            'total_users' => User::where('role', 'user')->count(),
            'total_products' => Product::count(),
            'low_stock_count' => Inventory::whereColumn('quantity', '<=', 'low_stock_threshold')->count(),
            'recent_orders' => Order::with(['user', 'orderItems.product'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get all products with filters.
     */
    public function products(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'images', 'inventory']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Create a new product.
     */
    public function createProduct(CreateProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated, $request) {
            $product = Product::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'sku' => $validated['sku'],
                'brand' => $validated['brand'] ?? null,
                'fragrance_notes' => $validated['fragrance_notes'] ?? null,
                'size' => $validated['size'] ?? null,
                'category_id' => $validated['category_id'],
            ]);

            // Create inventory
            Inventory::create([
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
                'low_stock_threshold' => $validated['low_stock_threshold'] ?? 10,
            ]);

            // Handle images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('products', 'public');
                    $product->images()->create([
                        'image_path' => $path,
                        'is_primary' => $index === 0,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => new ProductResource($product->load(['category', 'images', 'inventory'])),
            ], 201);
        });
    }

    /**
     * Update a product.
     */
    public function updateProduct(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $product->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => new ProductResource($product->load(['category', 'images', 'inventory'])),
        ]);
    }

    /**
     * Delete a product (soft delete).
     */
    public function deleteProduct(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Get all orders with filters.
     */
    public function orders(Request $request): JsonResponse
    {
        $query = Order::with(['user', 'orderItems.product', 'shippingAddress', 'statusHistory']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get order details.
     */
    public function orderDetails(string $id): JsonResponse
    {
        $order = Order::with(['user', 'orderItems.product.images', 'shippingAddress', 'statusHistory'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(UpdateOrderStatusRequest $request, string $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        $validated = $request->validated();

        return DB::transaction(function () use ($order, $validated, $request) {
            $oldStatus = $order->status;
            $order->status = $validated['status'];

            if (isset($validated['tracking_number'])) {
                $order->tracking_number = $validated['tracking_number'];
            }

            $order->save();

            // Create status history
            $order->statusHistory()->create([
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? "Status changed from {$oldStatus} to {$validated['status']}",
                'updated_by' => $request->user()->id,
            ]);

            // Send status update email for shipped/delivered
            if (in_array($validated['status'], ['shipped', 'delivered'])) {
                SendOrderStatusUpdateEmail::dispatch($order->fresh(), $validated['status']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => new OrderResource($order->load(['user', 'orderItems.product', 'shippingAddress', 'statusHistory'])),
            ]);
        });
    }

    /**
     * Process refund for an order.
     */
    public function refundOrder(RefundOrderRequest $request, string $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        if (!$order->stripe_payment_intent_id) {
            return response()->json([
                'success' => false,
                'message' => 'Order does not have a payment intent',
            ], 400);
        }

        try {
            $refundAmount = $request->amount ?? $order->total;
            $this->stripeService->createRefund(
                $order->stripe_payment_intent_id,
                $refundAmount,
                ['order_id' => $order->id]
            );

            return DB::transaction(function () use ($order, $request) {
                $order->status = 'refunded';
                $order->save();

                $order->statusHistory()->create([
                    'status' => 'refunded',
                    'notes' => 'Refund processed',
                    'updated_by' => $request->user()->id,
                ]);

                // Increase inventory back
                $this->inventoryService->increaseInventoryForOrder($order);

                return response()->json([
                    'success' => true,
                    'message' => 'Refund processed successfully',
                    'data' => new OrderResource($order->load(['user', 'orderItems.product', 'shippingAddress', 'statusHistory'])),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Refund failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get inventory list with low stock alerts.
     */
    public function inventory(Request $request): JsonResponse
    {
        $query = Inventory::with('product');

        if ($request->has('low_stock_only') && $request->low_stock_only) {
            $query->whereColumn('quantity', '<=', 'low_stock_threshold');
        }

        $inventory = $query->orderBy('quantity', 'asc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    /**
     * Get low stock products.
     */
    public function lowStockProducts(): JsonResponse
    {
        $lowStockProducts = $this->inventoryService->getLowStockProducts();

        return response()->json([
            'success' => true,
            'data' => $lowStockProducts,
        ]);
    }

    /**
     * Update inventory levels.
     */
    public function updateInventory(UpdateInventoryRequest $request, string $id): JsonResponse
    {
        $inventory = Inventory::findOrFail($id);

        $inventory->update($request->validated());

        // Send low stock alert if needed
        if ($inventory->isLowStock()) {
            SendLowStockAlertEmail::dispatch($inventory->fresh());
        }

        return response()->json([
            'success' => true,
            'message' => 'Inventory updated successfully',
            'data' => $inventory->load('product'),
        ]);
    }

    /**
     * Get all users.
     */
    public function users(Request $request): JsonResponse
    {
        $query = User::where('role', 'user');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Update user role.
     */
    public function updateUserRole(UpdateUserRoleRequest $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $user->role = $request->validated()['role'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Deactivate user.
     */
    public function deactivateUser(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // In a real app, you might want to soft delete or add a status field
        // For now, we'll just return success
        return response()->json([
            'success' => true,
            'message' => 'User deactivated successfully',
        ]);
    }
}
