<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Shop;
use App\Models\Batch;
use App\Models\EggCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'batch_id' => Batch::factory(),
            'egg_category_id' => EggCategory::factory(),
            'available_stock' => fake()->numberBetween(50, 500),
            'reserved_stock' => 0,
            'unit_price' => fake()->randomFloat(2, 5, 20),
        ];
    }

    /**
     * Set specific stock levels.
     */
    public function withStock(int $available, int $reserved = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'available_stock' => $available,
            'reserved_stock' => $reserved,
        ]);
    }

    /**
     * Indicate low stock status.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_stock' => fake()->numberBetween(10, 50),
        ]);
    }

    /**
     * Indicate out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_stock' => 0,
            'reserved_stock' => 0,
        ]);
    }
}
