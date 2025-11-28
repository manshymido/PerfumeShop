<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\RecentlyViewed;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Product management endpoints"
 * )
 */
class ProductController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     tags={"Products"},
     *     summary="Get list of products",
     *     description="Retrieve paginated list of products with optional filters",
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search term",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="brand",
     *         in="query",
     *         description="Filter by brand",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Minimum price",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Maximum price",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field",
     *         required=false,
     *         @OA\Schema(type="string", default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order (asc/desc)",
     *         required=false,
     *         @OA\Schema(type="string", default="desc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'images', 'inventory', 'reviews']);

        // Search
        if ($request->has('q') && $request->q) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhere('brand', 'like', "%{$searchTerm}%")
                  ->orWhere('sku', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by brand
        if ($request->has('brand')) {
            $query->where('brand', $request->brand);
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return $this->paginatedResponse(
            $products,
            ProductResource::collection($products->items())
        );
    }

    /**
     * Display the specified product with recently viewed tracking.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $product = Product::with(['category', 'images', 'inventory', 'reviews.user'])
            ->findOrFail($id);

        // Track recently viewed if user is authenticated
        if ($request->user()) {
            RecentlyViewed::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'product_id' => $product->id,
                ],
                ['viewed_at' => now()]
            );
        }

        return $this->successResponse([
            'product' => new ProductResource($product),
            'average_rating' => round((float) $product->reviews->avg('rating'), 2),
            'reviews_count' => $product->reviews->count(),
        ]);
    }

    /**
     * Get recently viewed products.
     */
    public function recentlyViewed(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return $this->errorResponse('Authentication required', null, 401);
        }

        $recentlyViewed = RecentlyViewed::where('user_id', $request->user()->id)
            ->with('product.images', 'product.inventory')
            ->orderBy('viewed_at', 'desc')
            ->limit(10)
            ->get()
            ->pluck('product');

        return $this->successResponse(ProductResource::collection($recentlyViewed));
    }

    /**
     * Get recommended products.
     */
    public function recommended(Request $request): JsonResponse
    {
        // Simple recommendation: products with highest ratings
        // In production, this could be based on purchase history, similar products, etc.
        $products = Product::with(['images', 'inventory'])
            ->withAvg('reviews', 'rating')
            ->having('reviews_avg_rating', '>', 4)
            ->orderBy('reviews_avg_rating', 'desc')
            ->limit(10)
            ->get();

        return $this->successResponse(ProductResource::collection($products));
    }

    /**
     * Get products by category.
     */
    public function byCategory(Request $request, string $categoryId): JsonResponse
    {
        $products = Product::where('category_id', $categoryId)
            ->with(['images', 'inventory', 'reviews'])
            ->paginate(15);

        return $this->paginatedResponse(
            $products,
            ProductResource::collection($products->items())
        );
    }
}
