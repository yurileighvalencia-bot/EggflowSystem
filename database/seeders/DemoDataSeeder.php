<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\DailyCollection;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\EggCategory;
use App\Models\Farm;
use App\Models\Inventory;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\RestockRequest;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\User;
use App\Models\WastageLog;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed the database with demo data for development and testing.
     */
    public function run(): void
    {
        $this->command->info('Seeding demo data...');

        // Get existing entities from DatabaseSeeder
        $farm = Farm::first();
        $shop = Shop::first();
        $categories = EggCategory::all();
        
        $manager = User::whereHas('roles', fn ($q) => $q->where('name', 'manager'))->first();
        $farmStaff = User::whereHas('roles', fn ($q) => $q->where('name', 'farm_staff'))->first();
        $shopStaff = User::whereHas('roles', fn ($q) => $q->where('name', 'shop_staff'))->first();

        // Create customers (using Customer model, not User)
        $customers = Customer::factory(15)->create();
        
        // Create some business customers with higher credit limits
        $businessCustomers = Customer::factory(3)->business()->create();
        $customers = $customers->merge($businessCustomers);
        
        $this->command->info('Created ' . $customers->count() . ' customers');

        // Create daily collections for the past 30 days
        $this->seedDailyCollections($farm, $farmStaff, $categories);
        
        // Create batches and inventory
        $batches = $this->seedBatchesAndInventory($farm, $shop, $farmStaff, $categories);
        
        // Create restock requests and deliveries
        $this->seedRestockAndDeliveries($shop, $farm, $batches, $shopStaff, $farmStaff, $categories);
        
        // Create reservations (now using Customer model)
        $this->seedReservations($shop, $customers, $categories);
        
        // Create sales
        $this->seedSales($shop, $shopStaff, $batches, $customers, $categories);
        
        // Create some wastage logs
        $this->seedWastage($shop, $batches, $shopStaff, $categories);

        $this->command->info('Demo data seeding complete!');
    }

    private function seedDailyCollections(Farm $farm, User $staff, $categories): void
    {
        foreach ($categories as $category) {
            for ($i = 30; $i >= 0; $i--) {
                DailyCollection::factory()->create([
                    'farm_id' => $farm->id,
                    'egg_category_id' => $category->id,
                    'collection_date' => now()->subDays($i)->format('Y-m-d'),
                    'quantity' => fake()->numberBetween(100, 500),
                    'staff_id' => $staff->id,
                ]);
            }
        }
        $this->command->info('Created daily collections for 30 days');
    }

    private function seedBatchesAndInventory(Farm $farm, Shop $shop, User $staff, $categories): array
    {
        $batches = [];
        
        foreach ($categories as $category) {
            // Create recent batches with good stock
            for ($i = 0; $i < 5; $i++) {
                $batch = Batch::factory()->create([
                    'farm_id' => $farm->id,
                    'egg_category_id' => $category->id,
                    'initial_quantity' => fake()->numberBetween(200, 500),
                    'current_quantity' => fake()->numberBetween(100, 300),
                    'collection_date' => now()->subDays(fake()->numberBetween(1, 10)),
                    'expires_at' => now()->addDays(fake()->numberBetween(20, 30)),
                    'created_by' => $staff->id,
                ]);
                $batches[] = $batch;

                // Create corresponding inventory at shop
                Inventory::factory()->create([
                    'shop_id' => $shop->id,
                    'batch_id' => $batch->id,
                    'egg_category_id' => $category->id,
                    'available_stock' => fake()->numberBetween(50, 150),
                    'reserved_stock' => fake()->numberBetween(0, 20),
                ]);
            }

            // Create one expiring soon batch per category
            $expiringBatch = Batch::factory()->expiringSoon()->create([
                'farm_id' => $farm->id,
                'egg_category_id' => $category->id,
                'created_by' => $staff->id,
            ]);
            $batches[] = $expiringBatch;

            Inventory::factory()->lowStock()->create([
                'shop_id' => $shop->id,
                'batch_id' => $expiringBatch->id,
                'egg_category_id' => $category->id,
            ]);
        }

        $this->command->info('Created batches and inventory');
        return $batches;
    }

    private function seedRestockAndDeliveries(Shop $shop, Farm $farm, array $batches, User $shopStaff, User $farmStaff, $categories): void
    {
        // Create pending restock requests
        foreach ($categories->take(2) as $category) {
            RestockRequest::factory()->create([
                'shop_id' => $shop->id,
                'egg_category_id' => $category->id,
                'requested_by' => $shopStaff->id,
            ]);
        }

        // Create acknowledged requests in transit
        foreach ($categories->skip(2)->take(2) as $category) {
            $request = RestockRequest::factory()->inTransit()->create([
                'shop_id' => $shop->id,
                'egg_category_id' => $category->id,
                'requested_by' => $shopStaff->id,
                'acknowledged_by' => $farmStaff->id,
            ]);

            // Create delivery for this request
            $delivery = Delivery::factory()->create([
                'shop_id' => $shop->id,
                'restock_request_id' => $request->id,
                'dispatched_by' => $farmStaff->id,
                'status' => Delivery::STATUS_DISPATCHED,
            ]);

            $batch = collect($batches)->where('egg_category_id', $category->id)->first();
            if ($batch) {
                DeliveryItem::factory()->pending()->create([
                    'delivery_id' => $delivery->id,
                    'batch_id' => $batch->id,
                    'egg_category_id' => $category->id,
                ]);
            }
        }

        // Create completed deliveries
        for ($i = 0; $i < 5; $i++) {
            $category = $categories->random();
            $delivery = Delivery::factory()->received()->create([
                'shop_id' => $shop->id,
                'dispatched_by' => $farmStaff->id,
                'received_by' => $shopStaff->id,
            ]);

            $batch = collect($batches)->where('egg_category_id', $category->id)->first();
            if ($batch) {
                DeliveryItem::factory()->create([
                    'delivery_id' => $delivery->id,
                    'batch_id' => $batch->id,
                    'egg_category_id' => $category->id,
                ]);
            }
        }

        $this->command->info('Created restock requests and deliveries');
    }

    private function seedReservations(Shop $shop, $customers, $categories): void
    {
        // Create pending reservations
        foreach ($customers->take(3) as $customer) {
            $reservation = Reservation::factory()->create([
                'shop_id' => $shop->id,
                'customer_id' => $customer->id,
            ]);

            $category = $categories->random();
            ReservationItem::factory()->create([
                'reservation_id' => $reservation->id,
                'egg_category_id' => $category->id,
            ]);
        }

        // Create confirmed reservations
        foreach ($customers->skip(3)->take(3) as $customer) {
            $reservation = Reservation::factory()->confirmed()->create([
                'shop_id' => $shop->id,
                'customer_id' => $customer->id,
            ]);

            ReservationItem::factory()->create([
                'reservation_id' => $reservation->id,
                'egg_category_id' => $categories->random()->id,
            ]);
        }

        // Create ready for pickup
        foreach ($customers->skip(6)->take(2) as $customer) {
            $reservation = Reservation::factory()->ready()->create([
                'shop_id' => $shop->id,
                'customer_id' => $customer->id,
            ]);

            ReservationItem::factory()->create([
                'reservation_id' => $reservation->id,
                'egg_category_id' => $categories->random()->id,
            ]);
        }

        // Create completed reservations
        foreach ($customers->skip(8)->take(2) as $customer) {
            $reservation = Reservation::factory()->completed()->create([
                'shop_id' => $shop->id,
                'customer_id' => $customer->id,
            ]);

            ReservationItem::factory()->create([
                'reservation_id' => $reservation->id,
                'egg_category_id' => $categories->random()->id,
            ]);
        }

        $this->command->info('Created reservations');
    }

    private function seedSales(Shop $shop, User $staff, array $batches, $customers, $categories): void
    {
        // Create walk-in sales (no customer)
        for ($i = 0; $i < 10; $i++) {
            $sale = Sale::factory()->walkIn()->create([
                'shop_id' => $shop->id,
                'staff_id' => $staff->id,
            ]);

            $category = $categories->random();
            $batch = collect($batches)->where('egg_category_id', $category->id)->first();
            
            if ($batch) {
                SaleItem::factory()->create([
                    'sale_id' => $sale->id,
                    'batch_id' => $batch->id,
                    'egg_category_id' => $category->id,
                ]);
            }
        }

        // Create sales with known customers
        foreach ($customers->take(8) as $customer) {
            $sale = Sale::factory()->create([
                'shop_id' => $shop->id,
                'staff_id' => $staff->id,
                'customer_id' => $customer->id,
            ]);

            $category = $categories->random();
            $batch = collect($batches)->where('egg_category_id', $category->id)->first();
            
            if ($batch) {
                SaleItem::factory()->create([
                    'sale_id' => $sale->id,
                    'batch_id' => $batch->id,
                    'egg_category_id' => $category->id,
                ]);
            }
        }

        // Create sales from reservations (already handled by reservation completion)
        $this->command->info('Created sales');
    }

    private function seedWastage(Shop $shop, array $batches, User $staff, $categories): void
    {
        foreach ($categories->take(2) as $category) {
            $batch = collect($batches)->where('egg_category_id', $category->id)->first();
            
            if ($batch) {
                // Spoilage
                WastageLog::factory()->create([
                    'shop_id' => $shop->id,
                    'batch_id' => $batch->id,
                    'egg_category_id' => $category->id,
                    'logged_by' => $staff->id,
                    'quantity' => fake()->numberBetween(5, 20),
                ]);

                // Batch expired
                WastageLog::factory()->batchExpired()->create([
                    'shop_id' => $shop->id,
                    'batch_id' => $batch->id,
                    'egg_category_id' => $category->id,
                    'logged_by' => $staff->id,
                ]);
            }
        }

        $this->command->info('Created wastage logs');
    }
}
