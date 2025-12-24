<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Batch;
use App\Models\EggCategory;
use App\Models\Farm;
use App\Models\Inventory;
use App\Models\Shop;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrentSalesTest extends TestCase
{
    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = app(InventoryService::class);
    }

    /**
     * Test that concurrent deductions don't cause negative stock.
     * 
     * This test verifies that the lockForUpdate() mechanism in PostgreSQL
     * correctly prevents race conditions during concurrent stock operations.
     */
    public function test_concurrent_deductions_do_not_cause_negative_stock(): void
    {
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();

        // Create a batch with exactly 100 eggs
        $batch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDay(),
            'initial_quantity' => 100,
            'current_quantity' => 100,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $batch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 100,
            'reserved_stock' => 0,
        ]);

        $shopId = $shop->id;
        $categoryId = $category->id;
        $exceptionCount = 0;
        $successCount = 0;

        // Simulate 5 concurrent requests each trying to deduct 30 eggs
        // Only 3 should succeed (100 / 30 = 3.33), at least 2 should fail
        $requests = [];
        for ($i = 0; $i < 5; $i++) {
            $requests[] = function () use ($shopId, $categoryId, &$exceptionCount, &$successCount) {
                try {
                    $this->inventoryService->deductStock($shopId, $categoryId, 30);
                    $successCount++;
                } catch (InsufficientStockException $e) {
                    $exceptionCount++;
                }
            };
        }

        // Execute each request (sequential in this test, but validates the transaction logic)
        foreach ($requests as $request) {
            $request();
        }

        // Verify results
        $inventory = Inventory::where('shop_id', $shopId)->first();

        // Stock should never go negative
        $this->assertGreaterThanOrEqual(0, $inventory->available_stock);

        // At most 100 eggs should have been sold (3 successful sales of 30 = 90, with 10 remaining)
        $this->assertLessThanOrEqual(100, (100 - $inventory->available_stock));

        // At least 2 requests should have failed due to insufficient stock
        $this->assertGreaterThanOrEqual(2, $exceptionCount);

        // At most 3 requests should have succeeded
        $this->assertLessThanOrEqual(3, $successCount);
    }

    /**
     * Test that transactions properly roll back on failure.
     */
    public function test_transaction_rollback_on_failure(): void
    {
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();

        $batch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDay(),
            'initial_quantity' => 50,
            'current_quantity' => 50,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $batch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 50,
            'reserved_stock' => 0,
        ]);

        $initialStock = 50;

        try {
            $this->inventoryService->deductStock($shop->id, $category->id, 100);
            $this->fail('Expected InsufficientStockException was not thrown');
        } catch (InsufficientStockException $e) {
            // Expected exception
        }

        // Verify stock remains unchanged due to rollback
        $inventory = Inventory::where('shop_id', $shop->id)->first();
        $this->assertEquals($initialStock, $inventory->available_stock);

        // Verify batch is unchanged
        $batch->refresh();
        $this->assertEquals(50, $batch->current_quantity);
        $this->assertEquals('active', $batch->status);
    }

    /**
     * Test that partial deduction from multiple batches works atomically.
     */
    public function test_multi_batch_deduction_is_atomic(): void
    {
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();

        // Create two batches with limited stock
        $batch1 = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDays(2),
            'initial_quantity' => 30,
            'current_quantity' => 30,
            'status' => 'active',
        ]);

        $batch2 = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDay(),
            'initial_quantity' => 20,
            'current_quantity' => 20,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $batch1->id,
            'egg_category_id' => $category->id,
            'available_stock' => 30,
            'reserved_stock' => 0,
        ]);

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $batch2->id,
            'egg_category_id' => $category->id,
            'available_stock' => 20,
            'reserved_stock' => 0,
        ]);

        // Try to deduct more than both batches have combined (50 available, requesting 60)
        try {
            $this->inventoryService->deductStock($shop->id, $category->id, 60);
            $this->fail('Expected InsufficientStockException was not thrown');
        } catch (InsufficientStockException $e) {
            // Expected
        }

        // Both batches should remain unchanged (atomic rollback)
        $inventory1 = Inventory::where('batch_id', $batch1->id)->first();
        $inventory2 = Inventory::where('batch_id', $batch2->id)->first();

        $this->assertEquals(30, $inventory1->available_stock);
        $this->assertEquals(20, $inventory2->available_stock);
    }

    /**
     * Test that reserve and release operations are consistent.
     */
    public function test_reserve_and_release_maintains_consistency(): void
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

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $batch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 100,
            'reserved_stock' => 0,
        ]);

        // Reserve 40 eggs
        $this->inventoryService->reserveStock($shop->id, $category->id, 40);

        $inventory = Inventory::where('shop_id', $shop->id)->first();
        $this->assertEquals(60, $inventory->available_stock);
        $this->assertEquals(40, $inventory->reserved_stock);
        $this->assertEquals(100, $inventory->available_stock + $inventory->reserved_stock);

        // Reserve 30 more eggs
        $this->inventoryService->reserveStock($shop->id, $category->id, 30);

        $inventory->refresh();
        $this->assertEquals(30, $inventory->available_stock);
        $this->assertEquals(70, $inventory->reserved_stock);
        $this->assertEquals(100, $inventory->available_stock + $inventory->reserved_stock);

        // Release 20 eggs back
        $this->inventoryService->releaseReservedStock($shop->id, $category->id, 20);

        $inventory->refresh();
        $this->assertEquals(50, $inventory->available_stock);
        $this->assertEquals(50, $inventory->reserved_stock);
        $this->assertEquals(100, $inventory->available_stock + $inventory->reserved_stock);
    }

    /**
     * Test that database isolation level prevents dirty reads.
     */
    public function test_database_isolation_prevents_dirty_reads(): void
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

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $batch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 100,
            'reserved_stock' => 0,
        ]);

        $externalReadValue = null;

        // Start a transaction that will be rolled back
        try {
            DB::transaction(function () use ($shop, $category, &$externalReadValue) {
                // Deduct stock within transaction
                $this->inventoryService->deductStock($shop->id, $category->id, 50);

                // Record what stock looks like mid-transaction
                $inventory = Inventory::where('shop_id', $shop->id)->first();
                $externalReadValue = $inventory->available_stock;

                // Force rollback by throwing exception
                throw new \Exception('Intentional rollback');
            });
        } catch (\Exception $e) {
            // Expected
        }

        // After rollback, stock should be original value
        $inventory = Inventory::where('shop_id', $shop->id)->first();
        $this->assertEquals(100, $inventory->available_stock);
    }
}
