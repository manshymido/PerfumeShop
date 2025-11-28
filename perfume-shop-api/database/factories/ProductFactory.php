<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true) . ' Perfume',
            'description' => fake()->optional()->paragraph(),
            'price' => fake()->randomFloat(2, 20, 500),
            'sku' => 'SKU-' . fake()->unique()->numerify('######'),
            'brand' => fake()->optional()->company(),
            'fragrance_notes' => fake()->optional()->sentence(),
            'size' => fake()->optional()->randomElement(['50ml', '100ml', '200ml']),
            'category_id' => Category::factory(),
        ];
    }
}

