<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MergeCartRequest;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Product;
use App\Services\InventoryService;
use App\Traits\ApiResponse;
use App\Traits\CartOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    use ApiResponse, CartOwnership;

    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get user's cart or guest cart.
     */
    public function index(Request $request): JsonResponse
    {
        $cartItems = $this->getCartItems($request);

        return $this->successResponse(CartResource::collection($cartItems));
    }

    /**
     * Add item to cart with stock validation.
     */
    public function store(StoreCartRequest $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-ID');

        // Find existing cart item
        $existingCartItem = null;
        if ($user) {
            $existingCartItem = Cart::where('user_id', $user->id)
                ->where('product_id', $request->product_id)
                ->first();
        } elseif ($sessionId) {
            $existingCartItem = Cart::where('session_id', $sessionId)
                ->where('product_id', $request->product_id)
                ->whereNull('user_id')
                ->first();
        } else {
            return $this->errorResponse('Session ID required for guest cart', null, 400);
        }

        // Calculate total quantity (existing + new)
        $totalQuantity = $request->quantity;
        if ($existingCartItem) {
            $totalQuantity = $existingCartItem->quantity + $request->quantity;
        }

        // Validate stock availability with total quantity
        $stockErrors = $this->inventoryService->validateStock([
            ['product_id' => $request->product_id, 'quantity' => $totalQuantity],
        ]);

        if (!empty($stockErrors)) {
            // Load product for better error message
            $product = Product::find($request->product_id);
            $productName = $product ? $product->name : "Product ID {$request->product_id}";
            
            // Improve error message
            $message = $existingCartItem 
                ? "Cannot add {$request->quantity} more. You already have {$existingCartItem->quantity} in your cart."
                : "Insufficient stock for {$productName}";
            
            return $this->errorResponse($message, $stockErrors, 422);
        }

        // Create or update cart item with accumulated quantity
        $cartData = [
            'product_id' => $request->product_id,
            'quantity' => $totalQuantity,
        ];

        if ($user) {
            $cartData['user_id'] = $user->id;
            $cartItem = Cart::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'product_id' => $request->product_id,
                ],
                $cartData
            );
        } else {
            $cartData['session_id'] = $sessionId;
            $cartItem = Cart::updateOrCreate(
                [
                    'session_id' => $sessionId,
                    'product_id' => $request->product_id,
                    'user_id' => null,
                ],
                $cartData
            );
        }

        $cartItem->load('product.images', 'product.inventory');

        return $this->successResponse(
            new CartResource($cartItem),
            'Item added to cart',
            201
        );
    }

    /**
     * Update cart item quantity with stock validation.
     */
    public function update(UpdateCartRequest $request, string $id): JsonResponse
    {

        $user = $request->user();
        $sessionId = $request->header('X-Session-ID');

        $cartItem = Cart::findOrFail($id);

        // Check ownership
        if (!$this->canAccessCartItem($request, $cartItem)) {
            return $this->unauthorizedCartResponse();
        }

        // Validate stock availability
        $stockErrors = $this->inventoryService->validateStock([
            ['product_id' => $cartItem->product_id, 'quantity' => $request->quantity],
        ]);

        if (!empty($stockErrors)) {
            $cartItem->load('product.inventory');
            $product = $cartItem->product;
            $productName = $product ? $product->name : "Product ID {$cartItem->product_id}";
            $availableStock = $product && $product->inventory ? $product->inventory->quantity : 0;
            $message = "Insufficient stock for {$productName}. Only {$availableStock} available.";
            return $this->errorResponse($message, $stockErrors, 422);
        }

        $cartItem->update(['quantity' => $request->quantity]);
        $cartItem->load('product.images', 'product.inventory');

        return $this->successResponse(
            new CartResource($cartItem),
            'Cart item updated'
        );
    }

    /**
     * Remove item from cart.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-ID');

        $cartItem = Cart::findOrFail($id);

        // Check ownership
        if (!$this->canAccessCartItem($request, $cartItem)) {
            return $this->unauthorizedCartResponse();
        }

        $cartItem->delete();

        return $this->successResponse(null, 'Item removed from cart');
    }

    /**
     * Clear entire cart.
     */
    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-ID');

        if ($user) {
            Cart::where('user_id', $user->id)->delete();
        } elseif ($sessionId) {
            Cart::where('session_id', $sessionId)->whereNull('user_id')->delete();
        } else {
            return $this->errorResponse('Session ID required for guest cart', null, 400);
        }

        return $this->successResponse(null, 'Cart cleared');
    }

    /**
     * Merge guest cart with user cart on login.
     */
    public function merge(MergeCartRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse('Authentication required', null, 401);
        }

        try {
        DB::transaction(function () use ($user, $request) {
            $guestCartItems = Cart::where('session_id', $request->session_id)
                ->whereNull('user_id')
                ->get();

                $itemsToValidate = [];

                foreach ($guestCartItems as $guestItem) {
                    $existingItem = Cart::where('user_id', $user->id)
                        ->where('product_id', $guestItem->product_id)
                        ->first();

                    if ($existingItem) {
                        // Calculate merged quantity
                        $mergedQuantity = $existingItem->quantity + $guestItem->quantity;
                        $itemsToValidate[] = [
                            'product_id' => $guestItem->product_id,
                            'quantity' => $mergedQuantity,
                        ];
                    } else {
                        // Just transfer, no merge needed
                        $itemsToValidate[] = [
                            'product_id' => $guestItem->product_id,
                            'quantity' => $guestItem->quantity,
                        ];
                    }
                }

                // Validate stock for all merged items
                if (!empty($itemsToValidate)) {
                    $stockErrors = $this->inventoryService->validateStock($itemsToValidate);
                    if (!empty($stockErrors)) {
                        throw new \Exception('Stock validation failed: ' . implode(', ', $stockErrors));
                    }
                }

                // Perform merge after validation
            foreach ($guestCartItems as $guestItem) {
                $existingItem = Cart::where('user_id', $user->id)
                    ->where('product_id', $guestItem->product_id)
                    ->first();

                if ($existingItem) {
                    // Merge quantities
                    $existingItem->quantity += $guestItem->quantity;
                    $existingItem->save();
                    $guestItem->delete();
                } else {
                    // Transfer to user cart
                    $guestItem->user_id = $user->id;
                    $guestItem->session_id = null;
                    $guestItem->save();
                }
            }
        });

        return $this->successResponse(null, 'Cart merged successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }
}
