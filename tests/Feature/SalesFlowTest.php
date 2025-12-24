<?php

namespace Tests\Feature;

use App\Events\SaleCompleted;
use App\Models\Batch;
use App\Models\EggCategory;
use App\Models\Farm;
use App\Models\Inventory;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Sale;
use App\Models\Shop;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SalesFlowTest extends TestCase
{
    /**
     * Test that a complete sale reduces inventory stock.
     */
    public function test_sale_reduces_inventory_stock(): void
    {
        Event::fake([SaleCompleted::class]);
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();
        $user = $this->actingAsShopStaff($shop);

        // Create batch and inventory
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

        // Make a sale
        $response = $this->postJson('/api/v1/sales', [
            'shop_id' => $shop->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'egg_category_id' => $category->id,
                    'quantity' => 30,
                    'unit_price' => $category->default_price,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', Sale::STATUS_COMPLETED);

        // Assert inventory was reduced
        $inventory->refresh();
        $this->assertEquals(70, $inventory->available_stock);

        // Assert batch quantity was reduced
        $batch->refresh();
        $this->assertEquals(70, $batch->current_quantity);

        // Assert sale was created with correct total
        $this->assertDatabaseHas('sales', [
            'shop_id' => $shop->id,
            'staff_id' => $user->id,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        // Assert SaleCompleted event was dispatched
        Event::assertDispatched(SaleCompleted::class);
    }

    /**
     * Test that sale creates sale items for each batch deduction.
     */
    public function test_sale_creates_sale_items_from_fifo_deductions(): void
    {
        Event::fake([SaleCompleted::class]);
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();
        $this->actingAsShopStaff($shop);

        // Create two batches
        $oldBatch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDays(3),
            'initial_quantity' => 20,
            'current_quantity' => 20,
            'status' => 'active',
        ]);

        $newBatch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDay(),
            'initial_quantity' => 50,
            'current_quantity' => 50,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $oldBatch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 20,
            'reserved_stock' => 0,
        ]);

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $newBatch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 50,
            'reserved_stock' => 0,
        ]);

        // Make a sale of 30 eggs (should span both batches)
        $response = $this->postJson('/api/v1/sales', [
            'shop_id' => $shop->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'egg_category_id' => $category->id,
                    'quantity' => 30,
                    'unit_price' => $category->default_price,
                ],
            ],
        ]);

        $response->assertCreated();

        $sale = Sale::with('items')->latest()->first();

        // Should have 2 sale items (20 from old batch, 10 from new batch)
        $this->assertCount(2, $sale->items);

        $oldBatchItem = $sale->items->where('batch_id', $oldBatch->id)->first();
        $newBatchItem = $sale->items->where('batch_id', $newBatch->id)->first();

        $this->assertEquals(20, $oldBatchItem->quantity);
        $this->assertEquals(10, $newBatchItem->quantity);
    }

    /**
     * Test that sale fails with insufficient stock.
     */
    public function test_sale_fails_with_insufficient_stock(): void
    {
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();
        $this->actingAsShopStaff($shop);

        $batch = Batch::factory()->create([
            'farm_id' => $farm->id,
            'egg_category_id' => $category->id,
            'collection_date' => now()->subDay(),
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'status' => 'active',
        ]);

        Inventory::factory()->create([
            'shop_id' => $shop->id,
            'batch_id' => $batch->id,
            'egg_category_id' => $category->id,
            'available_stock' => 10,
            'reserved_stock' => 0,
        ]);

        // Try to sell more than available
        $response = $this->postJson('/api/v1/sales', [
            'shop_id' => $shop->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'egg_category_id' => $category->id,
                    'quantity' => 50,
                    'unit_price' => $category->default_price,
                ],
            ],
        ]);

        // Should fail with insufficient stock error
        $response->assertStatus(422);

        // Inventory should remain unchanged (transaction rolled back)
        $this->assertEquals(10, Inventory::first()->available_stock);
    }

    /**
     * Test that sale calculates totals correctly.
     */
    public function test_sale_calculates_totals_correctly(): void
    {
        Event::fake([SaleCompleted::class]);
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();
        $category->update(['default_price' => 5.00]);
        $this->actingAsShopStaff($shop);

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

        $response = $this->postJson('/api/v1/sales', [
            'shop_id' => $shop->id,
            'payment_method' => 'cash',
            'discount_amount' => 10.00,
            'items' => [
                [
                    'egg_category_id' => $category->id,
                    'quantity' => 20,
                    'unit_price' => 5.00,
                ],
            ],
        ]);

        $response->assertCreated();

        $sale = Sale::latest()->first();

        // Subtotal: 20 * 5.00 = 100.00
        // Discount: 10.00
        // Total: 90.00
        $this->assertEquals(100.00, $sale->subtotal);
        $this->assertEquals(10.00, $sale->discount);
        $this->assertEquals(90.00, $sale->total);
    }

    /**
     * Test that SaleCompleted event is dispatched after sale.
     */
    public function test_sale_completed_event_is_dispatched(): void
    {
        Event::fake([SaleCompleted::class]);
        $this->seedEggCategories();

        $farm = Farm::factory()->create();
        $shop = Shop::factory()->forFarm($farm)->create();
        $category = EggCategory::first();
        $this->actingAsShopStaff($shop);

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

        $this->postJson('/api/v1/sales', [
            'shop_id' => $shop->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'egg_category_id' => $category->id,
                    'quantity' => 10,
                ],
            ],
        ]);

        Event::assertDispatched(SaleCompleted::class, function ($event) {
            return $event->sale instanceof Sale;
        });
    }

    /**
     * Test that customers can only view their own sales.
     */
    public function test_customer_can_only_view_own_sales(): void
    {
        $this->seedEggCategories();

        $shop = Shop::factory()->create();
        $customer = $this->actingAsCustomer();

        // Create a sale for this customer
        $ownSale = Sale::factory()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        // Create a sale for another customer
        $otherSale = Sale::factory()->create([
            'shop_id' => $shop->id,
            'customer_id' => null, // Different customer
            'status' => Sale::STATUS_COMPLETED,
        ]);

        $response = $this->getJson('/api/v1/sales');

        $response->assertOk();

        $saleIds = collect($response->json('data'))->pluck('id')->toArray();

        $this->assertContains($ownSale->id, $saleIds);
        $this->assertNotContains($otherSale->id, $saleIds);
    }

    /**
     * Test that shop staff can only view sales from their shop.
     */
    public function test_shop_staff_can_only_view_own_shop_sales(): void
    {
        $this->seedEggCategories();

        $shop = Shop::factory()->create();
        $otherShop = Shop::factory()->create();
        $this->actingAsShopStaff($shop);

        // Create sale for own shop
        $ownShopSale = Sale::factory()->create([
            'shop_id' => $shop->id,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        // Create sale for other shop
        $otherShopSale = Sale::factory()->create([
            'shop_id' => $otherShop->id,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        $response = $this->getJson('/api/v1/sales');

        $response->assertOk();

        $saleIds = collect($response->json('data'))->pluck('id')->toArray();

        $this->assertContains($ownShopSale->id, $saleIds);
        $this->assertNotContains($otherShopSale->id, $saleIds);
    }
}
