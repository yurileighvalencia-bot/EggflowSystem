<?php

namespace Database\Factories;

use App\Models\WastageLog;
use App\Models\Shop;
use App\Models\Batch;
use App\Models\Delivery;
use App\Models\EggCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WastageLog>
 */
class WastageLogFactory extends Factory
{
    protected $model = WastageLog::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'batch_id' => Batch::factory(),
            'delivery_id' => null,
            'egg_category_id' => EggCategory::factory(),
            'quantity' => fake()->numberBetween(1, 50),
            'source' => WastageLog::SOURCE_SHOP_SPOILAGE,
            'reason' => fake()->sentence(),
            'logged_by' => User::factory(),
            'logged_at' => now(),
        ];
    }

    /**
     * Indicate delivery rejection wastage.
     */
    public function deliveryRejection(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => WastageLog::SOURCE_DELIVERY_REJECTION,
            'delivery_id' => Delivery::factory(),
            'reason' => 'Rejected during delivery confirmation',
        ]);
    }

    /**
     * Indicate batch expired wastage.
     */
    public function batchExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => WastageLog::SOURCE_BATCH_EXPIRED,
            'reason' => 'Batch expired - automatic wastage',
        ]);
    }

    /**
     * Indicate inventory adjustment wastage.
     */
    public function inventoryAdjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => WastageLog::SOURCE_INVENTORY_ADJUSTMENT,
            'reason' => 'Manual inventory adjustment',
        ]);
    }
}
