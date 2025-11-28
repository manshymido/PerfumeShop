<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that users can view their own orders.
     */
    public function test_user_can_view_own_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    /**
     * Test that users cannot view other users' orders.
     */
    public function test_user_cannot_view_other_users_order(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(403);
    }

    /**
     * Test that admins can view any order.
     */
    public function test_admin_can_view_any_order(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200);
    }

    /**
     * Test that users can cancel their own orders.
     */
    public function test_user_can_cancel_own_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test that users cannot cancel other users' orders.
     */
    public function test_user_cannot_cancel_other_users_order(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user2->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(403);
    }
}

