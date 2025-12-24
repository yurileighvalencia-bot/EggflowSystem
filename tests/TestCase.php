<?php

namespace Tests;

use App\Models\Batch;
use App\Models\EggCategory;
use App\Models\Farm;
use App\Models\Inventory;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\EggCategoriesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * API version prefix for all API requests.
     */
    protected string $apiPrefix = '/api/v1';

    /**
     * Make a JSON GET request to the API.
     */
    protected function apiGet(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->getJson($this->apiPrefix . $uri);
    }

    /**
     * Make a JSON POST request to the API.
     */
    protected function apiPost(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson($this->apiPrefix . $uri, $data);
    }

    /**
     * Make a JSON PUT request to the API.
     */
    protected function apiPut(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->putJson($this->apiPrefix . $uri, $data);
    }

    /**
     * Make a JSON DELETE request to the API.
     */
    protected function apiDelete(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->deleteJson($this->apiPrefix . $uri);
    }

    /**
     * Seed the roles and permissions for authorization tests.
     */
    protected function seedPermissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Seed egg categories for inventory tests.
     */
    protected function seedEggCategories(): void
    {
        $this->seed(EggCategoriesSeeder::class);
    }

    /**
     * Create and authenticate a user with a specific role.
     */
    protected function actingAsRole(string $role, ?Farm $farm = null, ?Shop $shop = null): User
    {
        $this->seedPermissions();

        $user = User::factory()->create([
            'farm_id' => $farm?->id,
            'shop_id' => $shop?->id,
        ]);

        $user->assignRole($role);

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    /**
     * Create a manager user and authenticate.
     */
    protected function actingAsManager(): User
    {
        return $this->actingAsRole('manager');
    }

    /**
     * Create a farm staff user and authenticate.
     */
    protected function actingAsFarmStaff(?Farm $farm = null): User
    {
        $farm = $farm ?? Farm::factory()->create();
        return $this->actingAsRole('farm_staff', $farm);
    }

    /**
     * Create a shop staff user and authenticate.
     */
    protected function actingAsShopStaff(?Shop $shop = null): User
    {
        $shop = $shop ?? Shop::factory()->create();
        return $this->actingAsRole('shop_staff', null, $shop);
    }

    /**
     * Create a customer user and authenticate.
     */
    protected function actingAsCustomer(): User
    {
        return $this->actingAsRole('customer');
    }

    /**
     * Create a shop with inventory for a specific egg category.
     */
    protected function createShopWithInventory(
        int $availableStock = 100,
        int $reservedStock = 0,
        ?EggCategory $category = null,
        ?Batch $batch = null
    ): array {
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = $category ?? EggCategory::first() ?? EggCategory::factory()->create();
        $batch = $batch ?? Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'initial_quantity' => $availableStock + $reservedStock,
            'current_quantity' => $availableStock + $reservedStock,
        ]);

        $inventory = Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $batch->id,
            'egg_category_id' => $category->id,
            'available_stock' => $availableStock,
            'reserved_stock' => $reservedStock,
        ]);

        return compact('farm', 'shop', 'category', 'batch', 'inventory');
    }

    /**
     * Create multiple batches with FIFO ordering for testing stock deduction.
     */
    protected function createFIFOBatches(Shop $shop, EggCategory $category, array $quantities): array
    {
        $farm = $shop->farm ?? Farm::factory()->create();
        $batches = [];
        $inventories = [];

        foreach ($quantities as $index => $quantity) {
            $batch = Batch::factory()->create([
                'farm_id' => $farm->id,
                'egg_category_id' => $category->id,
                'collection_date' => now()->subDays(count($quantities) - $index), // Oldest first
                'initial_quantity' => $quantity,
                'current_quantity' => $quantity,
            ]);

            $inventory = Inventory::factory()->create([
                'shop_id' => $shop->id,
                'batch_id' => $batch->id,
                'egg_category_id' => $category->id,
                'available_stock' => $quantity,
                'reserved_stock' => 0,
            ]);

            $batches[] = $batch;
            $inventories[] = $inventory;
        }

        return compact('batches', 'inventories');
    }
}

