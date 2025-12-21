<?php

namespace Database\Seeders;

use App\Models\EggCategory;
use Illuminate\Database\Seeder;

class EggCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Small',
                'code' => 'SM',
                'description' => 'Small sized eggs (under 53g)',
                'low_stock_threshold' => 100,
                'restock_quantity' => 1000,
                'default_price' => 8.00,
                'sort_order' => 1,
            ],
            [
                'name' => 'Medium',
                'code' => 'MD',
                'description' => 'Medium sized eggs (53-63g)',
                'low_stock_threshold' => 150,
                'restock_quantity' => 1000,
                'default_price' => 10.00,
                'sort_order' => 2,
            ],
            [
                'name' => 'Large',
                'code' => 'LG',
                'description' => 'Large sized eggs (63-73g)',
                'low_stock_threshold' => 150,
                'restock_quantity' => 1000,
                'default_price' => 12.00,
                'sort_order' => 3,
            ],
            [
                'name' => 'Extra Large',
                'code' => 'XL',
                'description' => 'Extra large sized eggs (over 73g)',
                'low_stock_threshold' => 100,
                'restock_quantity' => 500,
                'default_price' => 14.00,
                'sort_order' => 4,
            ],
            [
                'name' => 'Cracked',
                'code' => 'CR',
                'description' => 'Cracked eggs - sold at discount',
                'low_stock_threshold' => 50,
                'restock_quantity' => 200,
                'default_price' => 5.00,
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            EggCategory::create($category);
        }
    }
}
