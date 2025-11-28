<?php

namespace Tests\Feature;

use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingAddressPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that users can view their own shipping addresses.
     */
    public function test_user_can_view_own_shipping_address(): void
    {
        $user = User::factory()->create();
        $address = ShippingAddress::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/shipping-addresses/{$address->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    /**
     * Test that users cannot view other users' shipping addresses.
     */
    public function test_user_cannot_view_other_users_shipping_address(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $address = ShippingAddress::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1, 'sanctum')
            ->getJson("/api/v1/shipping-addresses/{$address->id}");

        $response->assertStatus(403);
    }

    /**
     * Test that users can update their own shipping addresses.
     */
    public function test_user_can_update_own_shipping_address(): void
    {
        $user = User::factory()->create();
        $address = ShippingAddress::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/shipping-addresses/{$address->id}", [
                'full_name' => 'Updated Name',
                'address' => 'Updated Address',
                'city' => 'Updated City',
                'zip' => '12345',
                'country' => 'USA',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test that users cannot update other users' shipping addresses.
     */
    public function test_user_cannot_update_other_users_shipping_address(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $address = ShippingAddress::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1, 'sanctum')
            ->putJson("/api/v1/shipping-addresses/{$address->id}", [
                'full_name' => 'Malicious Update',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test that users can delete their own shipping addresses.
     */
    public function test_user_can_delete_own_shipping_address(): void
    {
        $user = User::factory()->create();
        $address = ShippingAddress::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/shipping-addresses/{$address->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test that users cannot delete other users' shipping addresses.
     */
    public function test_user_cannot_delete_other_users_shipping_address(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $address = ShippingAddress::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1, 'sanctum')
            ->deleteJson("/api/v1/shipping-addresses/{$address->id}");

        $response->assertStatus(403);
    }

    /**
     * Test that admins can view any shipping address.
     */
    public function test_admin_can_view_any_shipping_address(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $address = ShippingAddress::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/shipping-addresses/{$address->id}");

        $response->assertStatus(200);
    }
}

