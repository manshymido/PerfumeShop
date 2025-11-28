<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $table = 'inventory';

    protected $fillable = [
        'product_id',
        'quantity',
        'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }

    /**
     * Get the product that owns the inventory.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if stock is low.
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->low_stock_threshold;
    }

    /**
     * Check if product is in stock.
     */
    public function inStock(int $quantity = 1): bool
    {
        return $this->quantity >= $quantity;
    }

    /**
     * Decrease inventory quantity.
     */
    public function decrease(int $quantity): bool
    {
        if (!$this->inStock($quantity)) {
            return false;
        }

        $this->quantity -= $quantity;
        return $this->save();
    }

    /**
     * Increase inventory quantity.
     */
    public function increase(int $quantity): bool
    {
        $this->quantity += $quantity;
        return $this->save();
    }
}
