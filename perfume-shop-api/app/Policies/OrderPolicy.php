<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id || $user->isAdmin();
    }

    /**
     * Determine if the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own orders, admins can view all
    }

    /**
     * Determine if the user can create orders.
     */
    public function create(User $user): bool
    {
        return true; // Authenticated users can create orders
    }

    /**
     * Determine if the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        // Only admins can update orders (status changes)
        return $user->isAdmin();
    }

    /**
     * Determine if the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        // Users can cancel their own orders, admins can cancel any
        return ($user->id === $order->user_id || $user->isAdmin()) && $order->canBeCancelled();
    }

    /**
     * Determine if the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        // Only admins can delete orders
        return $user->isAdmin();
    }
}

