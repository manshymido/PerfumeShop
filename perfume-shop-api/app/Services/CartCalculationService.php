<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CartCalculationService
{
    /**
     * Calculate order totals from cart items.
     *
     * @param Collection $cartItems
     * @return array{subtotal: float, tax: float, shippingCost: float, total: float}
     */
    public function calculateTotals(Collection $cartItems): array
    {
        $subtotal = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        $taxRate = config('app.tax_rate', 0.10);
        $shippingCost = config('app.shipping_cost', 10.00);

        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax + $shippingCost;

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'shippingCost' => round($shippingCost, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Get tax rate.
     *
     * @return float
     */
    public function getTaxRate(): float
    {
        return config('app.tax_rate', 0.10);
    }

    /**
     * Get shipping cost.
     *
     * @return float
     */
    public function getShippingCost(): float
    {
        return config('app.shipping_cost', 10.00);
    }
}

