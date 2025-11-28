<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews for a product.
     */
    public function index(Request $request, string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $query = Review::where('product_id', $productId)
            ->with('user')
            ->orderBy('created_at', 'desc');

        // Filter by rating
        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        // Filter verified purchases only
        if ($request->has('verified_only') && $request->verified_only) {
            $query->where('verified_purchase', true);
        }

        $reviews = $query->paginate(10);

        // Get review statistics
        $stats = [
            'average_rating' => $product->reviews()->avg('rating') ?? 0,
            'total_reviews' => $product->reviews()->count(),
            'rating_distribution' => [
                5 => $product->reviews()->where('rating', 5)->count(),
                4 => $product->reviews()->where('rating', 4)->count(),
                3 => $product->reviews()->where('rating', 3)->count(),
                2 => $product->reviews()->where('rating', 2)->count(),
                1 => $product->reviews()->where('rating', 1)->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => ReviewResource::collection($reviews->items()),
                'statistics' => $stats,
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ],
            ],
        ]);
    }

    /**
     * Store a newly created review.
     */
    public function store(StoreReviewRequest $request, string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $user = $request->user();

        $validated = $request->validated();

        // Check if user already reviewed this product
        $existingReview = Review::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product',
            ], 400);
        }

        // Check if user has purchased this product (for verified purchase flag)
        $hasPurchased = Order::where('user_id', $user->id)
            ->whereHas('orderItems', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->whereIn('status', ['processing', 'shipped', 'delivered'])
            ->exists();

        $validated['user_id'] = $user->id;
        $validated['product_id'] = $productId;
        $validated['verified_purchase'] = $hasPurchased;

        $review = Review::create($validated);
        $review->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully',
            'data' => new ReviewResource($review),
        ], 201);
    }

    /**
     * Update the specified review.
     */
    public function update(UpdateReviewRequest $request, string $id): JsonResponse
    {
        $review = Review::findOrFail($id);

        $this->authorize('update', $review);

        $review->update($request->validated());
        $review->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully',
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $review = Review::findOrFail($id);

        $this->authorize('delete', $review);

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully',
        ]);
    }
}
