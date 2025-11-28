<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWishlistRequest;
use App\Http\Resources\WishlistResource;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * Display a listing of user's wishlist.
     */
    public function index(Request $request): JsonResponse
    {
        $wishlistItems = Wishlist::where('user_id', $request->user()->id)
            ->with('product.images', 'product.inventory')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => WishlistResource::collection($wishlistItems),
        ]);
    }

    /**
     * Store a newly created wishlist item.
     */
    public function store(StoreWishlistRequest $request): JsonResponse
    {
        $user = $request->user();
        $product = Product::findOrFail($request->product_id);

        // Check if already in wishlist - use updateOrCreate for idempotent operation
        $wishlistItem = Wishlist::firstOrCreate(
            [
            'user_id' => $user->id,
            'product_id' => $product->id,
            ]
        );

        $wishlistItem->load('product.images', 'product.inventory');

        // Check if it was just created or already existed
        $wasRecentlyCreated = $wishlistItem->wasRecentlyCreated;

        return response()->json([
            'success' => true,
            'message' => $wasRecentlyCreated 
                ? 'Product added to wishlist' 
                : 'Product already in wishlist',
            'data' => new WishlistResource($wishlistItem),
        ], $wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Remove the specified wishlist item.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $wishlistItem = Wishlist::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $wishlistItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from wishlist',
        ]);
    }

    /**
     * Move wishlist item to cart.
     */
    public function moveToCart(Request $request, string $id): JsonResponse
    {
        $wishlistItem = Wishlist::where('user_id', $request->user()->id)
            ->with('product.inventory')
            ->findOrFail($id);

        $user = $request->user();

        // Check if product is already in cart
        $existingCartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $wishlistItem->product_id)
            ->first();

        if ($existingCartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Product already in cart',
            ], 400);
        }

        // Check stock availability
        $inventory = $wishlistItem->product->inventory;
        if (!$inventory || !$inventory->inStock(1)) {
            return response()->json([
                'success' => false,
                'message' => 'Product is out of stock',
            ], 400);
        }

        // Add to cart
        $cartItem = Cart::create([
            'user_id' => $user->id,
            'product_id' => $wishlistItem->product_id,
            'quantity' => 1,
        ]);

        // Remove from wishlist
        $wishlistItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item moved to cart',
            'data' => $cartItem->load('product.images', 'product.inventory'),
        ]);
    }
}
