<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Farm;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run seeders in order
        $this->call([
            RolesAndPermissionsSeeder::class,
            EggCategoriesSeeder::class,
        ]);

        // Create a default farm
        $farm = Farm::create([
            'name' => 'Main Farm',
            'address' => '123 Farm Road, Countryside',
            'contact_person' => 'Farm Manager',
            'contact_phone' => '+1234567890',
            'contact_email' => 'farm@eggflow.local',
        ]);

        // Create a default shop
        $shop = Shop::create([
            'farm_id' => $farm->id,
            'name' => 'Main Shop',
            'address' => '456 Shop Street, Town Center',
            'contact_person' => 'Shop Manager',
            'contact_phone' => '+0987654321',
            'contact_email' => 'shop@eggflow.local',
        ]);

        // Create Manager user
        $manager = User::factory()->create([
            'name' => 'System Manager',
            'email' => 'manager@eggflow.local',
            'farm_id' => $farm->id,
            'shop_id' => $shop->id,
        ]);
        $manager->assignRole('manager');

        // Create Farm Staff user
        $farmStaff = User::factory()->create([
            'name' => 'Farm Staff',
            'email' => 'farmstaff@eggflow.local',
            'farm_id' => $farm->id,
        ]);
        $farmStaff->assignRole('farm_staff');

        // Create Shop Staff user
        $shopStaff = User::factory()->create([
            'name' => 'Shop Staff',
            'email' => 'shopstaff@eggflow.local',
            'shop_id' => $shop->id,
        ]);
        $shopStaff->assignRole('shop_staff');

        // Create Customer user
        $customer = User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@eggflow.local',
        ]);
        $customer->assignRole('customer');
    }
}
