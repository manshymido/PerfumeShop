<?php

namespace App\Policies;

use App\Models\ShippingAddress;
use App\Models\User;

class ShippingAddressPolicy
{
    /**
     * Determine if the user can view any shipping addresses.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own addresses
    }

    /**
     * Determine if the user can view the shipping address.
     */
    public function view(User $user, ShippingAddress $shippingAddress): bool
    {
        return $user->id === $shippingAddress->user_id || $user->isAdmin();
    }

    /**
     * Determine if the user can create shipping addresses.
     */
    public function create(User $user): bool
    {
        return true; // Authenticated users can create addresses
    }

    /**
     * Determine if the user can update the shipping address.
     */
    public function update(User $user, ShippingAddress $shippingAddress): bool
    {
        return $user->id === $shippingAddress->user_id || $user->isAdmin();
    }

    /**
     * Determine if the user can delete the shipping address.
     */
    public function delete(User $user, ShippingAddress $shippingAddress): bool
    {
        return $user->id === $shippingAddress->user_id || $user->isAdmin();
    }
}

