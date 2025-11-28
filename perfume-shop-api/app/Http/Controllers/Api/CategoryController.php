<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of categories.
     */
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')->get();

        return $this->successResponse(CategoryResource::collection($categories));
    }

    /**
     * Display the specified category with products.
     */
    public function show(string $id): JsonResponse
    {
        $category = Category::with(['products.images', 'products.inventory'])
            ->findOrFail($id);

        return $this->successResponse(new CategoryResource($category));
    }
}
