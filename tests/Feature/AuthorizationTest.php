<?php

namespace Tests\Feature;

use App\Models\Farm;
use App\Models\Shop;
use App\Models\User;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    /**
     * Test that managers can access farm management endpoints.
     */
    public function test_manager_can_list_farms(): void
    {
        $this->actingAsManager();

        Farm::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/farms');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'address', 'contact_phone', 'contact_email'],
                ],
            ]);
    }

    /**
     * Test that managers can create new farms.
     */
    public function test_manager_can_create_farm(): void
    {
        $this->actingAsManager();

        $response = $this->postJson('/api/v1/farms', [
            'name' => 'New Farm',
            'address' => '123 Farm Road',
            'contact_email' => 'farm@example.com',
            'contact_phone' => '1234567890',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Farm');

        $this->assertDatabaseHas('farms', ['name' => 'New Farm']);
    }

    /**
     * Test that farm staff cannot create farms.
     */
    public function test_farm_staff_cannot_create_farm(): void
    {
        $this->actingAsFarmStaff();

        $response = $this->postJson('/api/v1/farms', [
            'name' => 'Unauthorized Farm',
            'address' => '123 Farm Road',
            'contact_email' => 'farm@example.com',
            'contact_phone' => '1234567890',
        ]);

        $response->assertForbidden();
    }

    /**
     * Test that shop staff cannot access farm management.
     */
    public function test_shop_staff_cannot_access_farm_management(): void
    {
        $shop = Shop::factory()->create();
        $this->actingAsShopStaff($shop);

        $response = $this->postJson('/api/v1/farms', [
            'name' => 'Unauthorized Farm',
            'address' => '123 Farm Road',
            'contact_email' => 'farm@example.com',
            'contact_phone' => '1234567890',
        ]);

        $response->assertForbidden();
    }

    /**
     * Test that farm staff can only view their own farm.
     */
    public function test_farm_staff_can_view_own_farm(): void
    {
        $farm = Farm::factory()->create();
        $this->actingAsFarmStaff($farm);

        $response = $this->getJson("/api/v1/farms/{$farm->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $farm->id);
    }

    /**
     * Test that farm staff cannot view other farms.
     */
    public function test_farm_staff_cannot_view_other_farms(): void
    {
        $ownFarm = Farm::factory()->create();
        $otherFarm = Farm::factory()->create();
        $this->actingAsFarmStaff($ownFarm);

        $response = $this->getJson("/api/v1/farms/{$otherFarm->id}");

        $response->assertForbidden();
    }

    /**
     * Test that managers can manage users.
     */
    public function test_manager_can_list_users(): void
    {
        $this->actingAsManager();

        User::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email'],
                ],
            ]);
    }

    /**
     * Test that shop staff can view their assigned shop.
     */
    public function test_shop_staff_can_view_own_shop(): void
    {
        $shop = Shop::factory()->create();
        $this->actingAsShopStaff($shop);

        $response = $this->getJson("/api/v1/shops/{$shop->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $shop->id);
    }

    /**
     * Test that shop staff cannot view other shops.
     */
    public function test_shop_staff_cannot_view_other_shops(): void
    {
        $ownShop = Shop::factory()->create();
        $otherShop = Shop::factory()->create();
        $this->actingAsShopStaff($ownShop);

        $response = $this->getJson("/api/v1/shops/{$otherShop->id}");

        $response->assertForbidden();
    }

    /**
     * Test that unauthenticated users cannot access protected endpoints.
     */
    public function test_unauthenticated_user_cannot_access_farms(): void
    {
        $response = $this->getJson('/api/v1/farms');

        $response->assertUnauthorized();
    }

    /**
     * Test that customers cannot access admin endpoints.
     */
    public function test_customer_cannot_access_farm_management(): void
    {
        $this->actingAsCustomer();

        $response = $this->postJson('/api/v1/farms', [
            'name' => 'Customer Farm Attempt',
            'address' => '123 Farm Road',
            'contact_email' => 'farm@example.com',
            'contact_phone' => '1234567890',
        ]);

        $response->assertForbidden();
    }

    /**
     * Test that managers can assign roles to users.
     */
    public function test_manager_can_assign_role(): void
    {
        $this->actingAsManager();

        $user = User::factory()->create();

        $response = $this->postJson("/api/v1/users/{$user->id}/role", [
            'role' => 'shop_staff',
        ]);

        $response->assertOk();
        $this->assertTrue($user->fresh()->hasRole('shop_staff'));
    }

    /**
     * Test that non-managers cannot assign roles.
     */
    public function test_farm_staff_cannot_assign_role(): void
    {
        $this->actingAsFarmStaff();

        $user = User::factory()->create();

        $response = $this->postJson("/api/v1/users/{$user->id}/role", [
            'role' => 'shop_staff',
        ]);

        $response->assertForbidden();
    }
}
