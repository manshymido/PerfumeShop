<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that regular users cannot access admin endpoints.
     */
    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/stats');

        $response->assertStatus(403);
    }

    /**
     * Test that admin users can access admin endpoints.
     */
    public function test_admin_user_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    /**
     * Test that unauthenticated users cannot access admin endpoints.
     */
    public function test_unauthenticated_user_cannot_access_admin_endpoints(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard/stats');

        $response->assertStatus(401);
    }

    /**
     * Test that regular users cannot access admin product management.
     */
    public function test_regular_user_cannot_create_products(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/products', [
                'name' => 'Test Product',
                'price' => 99.99,
                'sku' => 'TEST-001',
                'category_id' => 1,
                'quantity' => 10,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test that admin users can create products.
     */
    public function test_admin_user_can_create_products(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/products', [
                'name' => 'Test Product',
                'price' => 99.99,
                'sku' => 'TEST-001',
                'category_id' => 1,
                'quantity' => 10,
            ]);

        // Will fail validation but should pass authorization (403 vs 422)
        $this->assertNotEquals(403, $response->status());
    }
}

