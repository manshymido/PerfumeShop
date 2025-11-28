<?php

namespace App\Traits;

use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait CartOwnership
{
    /**
     * Check if the user owns the cart item or if it belongs to the guest session.
     *
     * @param Request $request
     * @param Cart $cartItem
     * @return bool
     */
    protected function canAccessCartItem(Request $request, Cart $cartItem): bool
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-ID');

        if ($user) {
            return $cartItem->user_id === $user->id;
        }

        return $cartItem->session_id === $sessionId && $cartItem->user_id === null;
    }

    /**
     * Return unauthorized response for cart access.
     *
     * @return JsonResponse
     */
    protected function unauthorizedCartResponse(): JsonResponse
    {
        return $this->errorResponse('Unauthorized', null, 403);
    }

    /**
     * Get cart items for user or guest session.
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getCartItems(Request $request)
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-ID');

        if ($user) {
            return Cart::where('user_id', $user->id)
                ->with('product.images', 'product.inventory')
                ->get();
        } elseif ($sessionId) {
            return Cart::where('session_id', $sessionId)
                ->whereNull('user_id')
                ->with('product.images', 'product.inventory')
                ->get();
        }

        return collect();
    }

    /**
     * Get cart items for authenticated users with session_id fallback.
     * This handles cases where cart items might not be migrated yet.
     *
     * @param Request $request
     * @param array $with Relationships to eager load
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getCartItemsWithSessionFallback(Request $request, array $with = ['product.inventory'])
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-ID');

        if ($user) {
            // For authenticated users, check both user_id and session_id
            // This handles cases where cart items might not be migrated yet
            return Cart::where(function ($query) use ($user, $sessionId) {
                $query->where('user_id', $user->id);
                if ($sessionId) {
                    $query->orWhere(function ($q) use ($sessionId) {
                        $q->where('session_id', $sessionId)
                          ->whereNull('user_id');
                    });
                }
            })
            ->with($with)
            ->get();
        } elseif ($sessionId) {
            return Cart::where('session_id', $sessionId)
                ->whereNull('user_id')
                ->with($with)
                ->get();
        }

        return collect();
    }

    /**
     * Clear cart items for user or guest session with session fallback.
     *
     * @param Request $request
     * @return int Number of deleted items
     */
    protected function clearCartWithSessionFallback(Request $request): int
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-ID');

        if ($user) {
            return Cart::where(function ($query) use ($user, $sessionId) {
                $query->where('user_id', $user->id);
                if ($sessionId) {
                    $query->orWhere(function ($q) use ($sessionId) {
                        $q->where('session_id', $sessionId)
                          ->whereNull('user_id');
                    });
                }
            })->delete();
        } elseif ($sessionId) {
            return Cart::where('session_id', $sessionId)
                ->whereNull('user_id')
                ->delete();
        }

        return 0;
    }
}

