<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Batch;
use App\Models\EggCategory;
use App\Models\Farm;
use App\Models\Inventory;
use App\Models\Shop;
use App\Services\InventoryService;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = app(InventoryService::class);
    }

    /**
     * Test that stock deduction follows FIFO order (oldest batch first).
     */
    public function test_deduct_stock_follows_fifo_order(): void
    {
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();

        // Create 3 batches with different collection dates
        $oldestBatch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDays(3),
            'initial_quantity' => 50,
            'current_quantity' => 50,
            'status' => 'active',
        ]);

        $middleBatch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDays(2),
            'initial_quantity' => 50,
            'current_quantity' => 50,
            'status' => 'active',
        ]);

        $newestBatch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDays(1),
            'initial_quantity' => 50,
            'current_quantity' => 50,
            'status' => 'active',
        ]);

        // Create inventory for each batch
        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $oldestBatch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 50,
            'reserved_stock' => 0,
        ]);

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $middleBatch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 50,
            'reserved_stock' => 0,
        ]);

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $newestBatch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 50,
            'reserved_stock' => 0,
        ]);

        // Deduct 70 eggs - should take 50 from oldest + 20 from middle
        $deductions = $this->inventoryService->deductStock($shop->id, $category->id, 70);

        // Assert deductions are in FIFO order
        $this->assertCount(2, $deductions);
        $this->assertEquals($oldestBatch->id, $deductions[0]['batch_id']);
        $this->assertEquals(50, $deductions[0]['quantity']);
        $this->assertEquals($middleBatch->id, $deductions[1]['batch_id']);
        $this->assertEquals(20, $deductions[1]['quantity']);

        // Verify database state
        $this->assertEquals(0, Inventory::where('batch_id', $oldestBatch->id)->first()->available_stock);
        $this->assertEquals(30, Inventory::where('batch_id', $middleBatch->id)->first()->available_stock);
        $this->assertEquals(50, Inventory::where('batch_id', $newestBatch->id)->first()->available_stock);

        // Verify batch statuses
        $this->assertEquals('depleted', $oldestBatch->fresh()->status);
        $this->assertEquals('active', $middleBatch->fresh()->status);
        $this->assertEquals('active', $newestBatch->fresh()->status);
    }

    /**
     * Test that depleting a batch changes its status.
     */
    public function test_depleting_batch_changes_status_to_depleted(): void
    {
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();

        $batch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDay(),
            'initial_quantity' => 30,
            'current_quantity' => 30,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $batch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 30,
            'reserved_stock' => 0,
        ]);

        // Deduct exactly the available amount
        $this->inventoryService->deductStock($shop->id, $category->id, 30);

        // Batch should be depleted
        $this->assertEquals('depleted', $batch->fresh()->status);
        $this->assertEquals(0, $batch->fresh()->current_quantity);
    }

    /**
     * Test that attempting to deduct more than available throws exception.
     */
    public function test_insufficient_stock_throws_exception(): void
    {
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();

        $batch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDay(),
            'initial_quantity' => 20,
            'current_quantity' => 20,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $batch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 20,
            'reserved_stock' => 0,
        ]);

        $this->expectException(InsufficientStockException::class);

        // Try to deduct more than available
        $this->inventoryService->deductStock($shop->id, $category->id, 50);
    }

    /**
     * Test that reserving stock moves it from available to reserved.
     */
    public function test_reserve_stock_moves_from_available_to_reserved(): void
    {
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();

        $batch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDay(),
            'initial_quantity' => 100,
            'current_quantity' => 100,
            'status' => 'active',
        ]);

        $inventory = Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $batch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 100,
            'reserved_stock' => 0,
        ]);

        // Reserve 30 eggs
        $result = $this->inventoryService->reserveStock($shop->id, $category->id, 30);

        $this->assertTrue($result);

        $inventory->refresh();
        $this->assertEquals(70, $inventory->available_stock);
        $this->assertEquals(30, $inventory->reserved_stock);
    }

    /**
     * Test that releasing reserved stock moves it back to available.
     */
    public function test_release_reserved_stock_moves_back_to_available(): void
    {
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();

        $batch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDay(),
            'initial_quantity' => 100,
            'current_quantity' => 100,
            'status' => 'active',
        ]);

        $inventory = Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $batch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 70,
            'reserved_stock' => 30,
        ]);

        // Release 20 eggs
        $result = $this->inventoryService->releaseReservedStock($shop->id, $category->id, 20);

        $this->assertTrue($result);

        $inventory->refresh();
        $this->assertEquals(90, $inventory->available_stock);
        $this->assertEquals(10, $inventory->reserved_stock);
    }

    /**
     * Test deduction skips depleted batches.
     */
    public function test_deduct_stock_skips_depleted_batches(): void
    {
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();

        // Create a depleted batch (oldest)
        $depletedBatch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDays(5),
            'initial_quantity' => 100,
            'current_quantity' => 0,
            'status' => 'depleted',
        ]);

        // Create an active batch (newer)
        $activeBatch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDays(2),
            'initial_quantity' => 50,
            'current_quantity' => 50,
            'status' => 'active',
        ]);

        // Depleted batch has no available stock
        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $depletedBatch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 0,
            'reserved_stock' => 0,
        ]);

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $activeBatch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 50,
            'reserved_stock' => 0,
        ]);

        // Deduct 30 eggs
        $deductions = $this->inventoryService->deductStock($shop->id, $category->id, 30);

        // Should only deduct from active batch, not the depleted one
        $this->assertCount(1, $deductions);
        $this->assertEquals($activeBatch->id, $deductions[0]['batch_id']);
        $this->assertEquals(30, $deductions[0]['quantity']);
    }

    /**
     * Test that multiple deductions across batches work correctly.
     */
    public function test_multiple_deductions_across_all_batches(): void
    {
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();

        // Create 3 batches with 30 eggs each
        $batches = [];
        for ($i = 0; $i < 3; $i++) {
            $batch = Batch::factory()->create([
                'farm_id' => $farm->id,
                'egg_category_id' => $category->id,
                'collection_date' => now()->subDays(3 - $i),
                'initial_quantity' => 30,
                'current_quantity' => 30,
                'status' => 'active',
            ]);

            Inventory::factory()->create([
                'shop_id' => $shop->id,
                'batch_id' => $batch->id,
                'egg_category_id' => $category->id,
                'available_stock' => 30,
                'reserved_stock' => 0,
            ]);

            $batches[] = $batch;
        }

        // Deduct 80 eggs - should take all 90 but only 80
        $deductions = $this->inventoryService->deductStock($shop->id, $category->id, 80);

        // Should have 3 deductions: 30 + 30 + 20
        $this->assertCount(3, $deductions);
        $this->assertEquals(30, $deductions[0]['quantity']);
        $this->assertEquals(30, $deductions[1]['quantity']);
        $this->assertEquals(20, $deductions[2]['quantity']);

        // First two batches should be depleted
        $this->assertEquals('depleted', $batches[0]->fresh()->status);
        $this->assertEquals('depleted', $batches[1]->fresh()->status);
        $this->assertEquals('active', $batches[2]->fresh()->status);
    }
}
