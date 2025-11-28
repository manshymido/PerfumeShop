<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that users can update their own reviews.
     */
    public function test_user_can_update_own_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/reviews/{$review->id}", [
                'rating' => 5,
                'comment' => 'Updated review',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test that users cannot update other users' reviews.
     */
    public function test_user_cannot_update_other_users_review(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->putJson("/api/v1/reviews/{$review->id}", [
                'rating' => 1,
                'comment' => 'Malicious update',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test that admins can update any review.
     */
    public function test_admin_can_update_any_review(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/reviews/{$review->id}", [
                'rating' => 5,
                'comment' => 'Admin update',
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test that users can delete their own reviews.
     */
    public function test_user_can_delete_own_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/reviews/{$review->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test that users cannot delete other users' reviews.
     */
    public function test_user_cannot_delete_other_users_review(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user2->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->deleteJson("/api/v1/reviews/{$review->id}");

        $response->assertStatus(403);
    }
}

