<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Decrease inventory for order items.
     */
    public function decreaseInventoryForOrder(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            foreach ($order->orderItems as $item) {
                // Use lockForUpdate() to prevent race conditions
                $inventory = Inventory::where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();
                
                if (!$inventory || !$inventory->inStock($item->quantity)) {
                    throw new \Exception("Insufficient stock for product ID: {$item->product_id}");
                }

                $inventory->decrease($item->quantity);
            }

            return true;
        });
    }

    /**
     * Increase inventory for order items (for cancellation/refund).
     */
    public function increaseInventoryForOrder(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            foreach ($order->orderItems as $item) {
                // Use lockForUpdate() to prevent race conditions
                $inventory = Inventory::where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();
                
                if ($inventory) {
                    $inventory->increase($item->quantity);
                }
            }

            return true;
        });
    }

    /**
     * Validate stock availability for cart items.
     */
    public function validateStock(array $items): array
    {
        $errors = [];

        // Use shared lock for read operations to ensure consistency
        foreach ($items as $item) {
            $inventory = Inventory::where('product_id', $item['product_id'])
                ->sharedLock()
                ->first();
            
            if (!$inventory) {
                $errors[] = "Product ID {$item['product_id']} not found in inventory";
                continue;
            }

            if (!$inventory->inStock($item['quantity'])) {
                $errors[] = "Insufficient stock for product ID: {$item['product_id']}. Available: {$inventory->quantity}, Requested: {$item['quantity']}";
            }
        }

        return $errors;
    }

    /**
     * Get low stock products.
     */
    public function getLowStockProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Inventory::whereColumn('quantity', '<=', 'low_stock_threshold')
            ->with('product')
            ->get();
    }
}

