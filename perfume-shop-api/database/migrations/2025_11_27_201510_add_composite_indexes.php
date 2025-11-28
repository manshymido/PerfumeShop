<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Composite index for user orders filtered by status and sorted by created_at
            $table->index(['user_id', 'status', 'created_at'], 'orders_user_status_created_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            // Composite index for category filtering with price sorting
            $table->index(['category_id', 'price'], 'products_category_price_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_status_created_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_category_price_idx');
        });
    }
};
