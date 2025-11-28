<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Men\'s Fragrances',
                'slug' => 'mens-fragrances',
                'description' => 'Premium men\'s perfumes and colognes',
            ],
            [
                'name' => 'Women\'s Fragrances',
                'slug' => 'womens-fragrances',
                'description' => 'Elegant women\'s perfumes and eau de parfum',
            ],
            [
                'name' => 'Unisex Fragrances',
                'slug' => 'unisex-fragrances',
                'description' => 'Versatile fragrances for everyone',
            ],
            [
                'name' => 'Luxury Collection',
                'slug' => 'luxury-collection',
                'description' => 'Exclusive high-end fragrances',
            ],
            [
                'name' => 'Floral',
                'slug' => 'floral',
                'description' => 'Fresh and floral scented perfumes',
            ],
            [
                'name' => 'Oriental',
                'slug' => 'oriental',
                'description' => 'Rich and exotic oriental fragrances',
            ],
            [
                'name' => 'Woody',
                'slug' => 'woody',
                'description' => 'Warm and earthy woody scents',
            ],
            [
                'name' => 'Fresh',
                'slug' => 'fresh',
                'description' => 'Light and refreshing fragrances',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('Categories seeded successfully!');
    }
}
