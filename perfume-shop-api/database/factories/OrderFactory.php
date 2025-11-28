<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 1000);
        $tax = $subtotal * 0.10;
        $shippingCost = 10.00;
        $total = $subtotal + $tax + $shippingCost;

        return [
            'user_id' => User::factory(),
            'shipping_address_id' => ShippingAddress::factory(),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_cost' => $shippingCost,
            'total' => $total,
            'status' => fake()->randomElement(['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded']),
            'stripe_payment_intent_id' => 'pi_' . fake()->unique()->sha1(),
            'tracking_number' => fake()->optional()->numerify('TRACK#######'),
        ];
    }
}

