<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// API v1 routes with rate limiting
Route::prefix('v1')->middleware(['throttle:60,1'])->group(function () {
    // Public routes
    Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/forgot-password', [\App\Http\Controllers\Api\AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword']);

    // Public product routes (order matters - specific routes before parameterized routes)
    Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::get('/products/recommended', [\App\Http\Controllers\Api\ProductController::class, 'recommended']);
    Route::get('/products/category/{categoryId}', [\App\Http\Controllers\Api\ProductController::class, 'byCategory']);
    Route::get('/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'show']);
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'show']);

    // Cart routes (support both authenticated and guest users)
    Route::middleware(['optional.auth'])->group(function () {
        Route::get('/cart', [\App\Http\Controllers\Api\CartController::class, 'index']);
        Route::post('/cart', [\App\Http\Controllers\Api\CartController::class, 'store']);
        Route::put('/cart/{id}', [\App\Http\Controllers\Api\CartController::class, 'update']);
        Route::delete('/cart/{id}', [\App\Http\Controllers\Api\CartController::class, 'destroy']);
        Route::delete('/cart', [\App\Http\Controllers\Api\CartController::class, 'clear']);
    });

    // Authenticated routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/user', [\App\Http\Controllers\Api\AuthController::class, 'user']);
        Route::put('/user', [\App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
        
        Route::get('/products/recently-viewed', [\App\Http\Controllers\Api\ProductController::class, 'recentlyViewed']);
        
        // Cart merge route (requires authentication)
        Route::post('/cart/merge', [\App\Http\Controllers\Api\CartController::class, 'merge']);
        
        // Shipping address routes
        Route::apiResource('shipping-addresses', \App\Http\Controllers\Api\ShippingAddressController::class);
        
        // Checkout routes
        Route::post('/checkout/validate', [\App\Http\Controllers\Api\StripeController::class, 'validateCheckout']);
        Route::post('/checkout/create-intent', [\App\Http\Controllers\Api\StripeController::class, 'createPaymentIntent']);
        Route::post('/checkout/update-intent', [\App\Http\Controllers\Api\StripeController::class, 'updatePaymentIntent']);
        
        // Order routes
        Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
        Route::post('/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']);
        Route::get('/orders/{id}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
        Route::put('/orders/{id}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'cancel']);
        Route::get('/orders/{id}/invoice', [\App\Http\Controllers\Api\OrderController::class, 'invoice']);
        
        // Review routes
        Route::get('/products/{id}/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'index']);
        Route::post('/products/{id}/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'store']);
        Route::put('/reviews/{id}', [\App\Http\Controllers\Api\ReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [\App\Http\Controllers\Api\ReviewController::class, 'destroy']);
        
        // Wishlist routes
        Route::get('/wishlist', [\App\Http\Controllers\Api\WishlistController::class, 'index']);
        Route::post('/wishlist', [\App\Http\Controllers\Api\WishlistController::class, 'store']);
        Route::delete('/wishlist/{id}', [\App\Http\Controllers\Api\WishlistController::class, 'destroy']);
        Route::post('/wishlist/{id}/move-to-cart', [\App\Http\Controllers\Api\WishlistController::class, 'moveToCart']);
    });

    // Stripe webhook (no auth required)
    Route::post('/webhooks/stripe', [\App\Http\Controllers\Api\StripeController::class, 'handleWebhook']);
});

// Admin routes with stricter rate limiting
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'admin', 'throttle:30,1'])->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [\App\Http\Controllers\Api\AdminController::class, 'dashboardStats']);
    
    // Products
    Route::get('/products', [\App\Http\Controllers\Api\AdminController::class, 'products']);
    Route::post('/products', [\App\Http\Controllers\Api\AdminController::class, 'createProduct']);
    Route::put('/products/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updateProduct']);
    Route::delete('/products/{id}', [\App\Http\Controllers\Api\AdminController::class, 'deleteProduct']);
    
    // Orders
    Route::get('/orders', [\App\Http\Controllers\Api\AdminController::class, 'orders']);
    Route::get('/orders/{id}', [\App\Http\Controllers\Api\AdminController::class, 'orderDetails']);
    Route::put('/orders/{id}/status', [\App\Http\Controllers\Api\AdminController::class, 'updateOrderStatus']);
    Route::post('/orders/{id}/refund', [\App\Http\Controllers\Api\AdminController::class, 'refundOrder']);
    
    // Inventory
    Route::get('/inventory', [\App\Http\Controllers\Api\AdminController::class, 'inventory']);
    Route::get('/inventory/low-stock', [\App\Http\Controllers\Api\AdminController::class, 'lowStockProducts']);
    Route::put('/inventory/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updateInventory']);
    
    // Users
    Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'users']);
    Route::put('/users/{id}/role', [\App\Http\Controllers\Api\AdminController::class, 'updateUserRole']);
    Route::delete('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'deactivateUser']);
});

