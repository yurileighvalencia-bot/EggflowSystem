<?php

namespace Database\Factories;

use App\Models\RestockRequest;
use App\Models\Shop;
use App\Models\EggCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RestockRequest>
 */
class RestockRequestFactory extends Factory
{
    protected $model = RestockRequest::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(100, 500);

        return [
            'shop_id' => Shop::factory(),
            'egg_category_id' => EggCategory::factory(),
            'quantity_requested' => $quantity,
            'quantity_fulfilled' => 0,
            'quantity_remaining' => $quantity,
            'status' => RestockRequest::STATUS_PENDING,
            'requested_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the request is acknowledged.
     */
    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RestockRequest::STATUS_ACKNOWLEDGED,
            'acknowledged_by' => User::factory(),
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Indicate that the request is in transit.
     */
    public function inTransit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RestockRequest::STATUS_IN_TRANSIT,
            'acknowledged_by' => User::factory(),
            'acknowledged_at' => now()->subHours(2),
        ]);
    }

    /**
     * Indicate that the request is delivered.
     */
    public function delivered(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity_requested'];
            return [
                'status' => RestockRequest::STATUS_DELIVERED,
                'quantity_fulfilled' => $quantity,
                'quantity_remaining' => 0,
                'acknowledged_by' => User::factory(),
                'acknowledged_at' => now()->subHours(4),
            ];
        });
    }
}
