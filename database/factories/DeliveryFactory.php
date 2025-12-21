<?php

namespace Database\Factories;

use App\Models\Delivery;
use App\Models\RestockRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Delivery>
 */
class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'restock_request_id' => RestockRequest::factory(),
            'shop_id' => Shop::factory(),
            'dispatched_by' => User::factory(),
            'status' => Delivery::STATUS_DISPATCHED,
            'dispatched_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the delivery is received.
     */
    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Delivery::STATUS_RECEIVED,
            'received_by' => User::factory(),
            'received_at' => now(),
        ]);
    }

    /**
     * Indicate that the delivery is partial.
     */
    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Delivery::STATUS_PARTIAL,
            'received_by' => User::factory(),
            'received_at' => now(),
        ]);
    }

    /**
     * Indicate that the delivery is disputed.
     */
    public function disputed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Delivery::STATUS_DISPUTED,
            'received_by' => User::factory(),
            'received_at' => now(),
        ]);
    }
}
