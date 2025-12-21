<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Farm;
use App\Models\EggCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Batch>
 */
class BatchFactory extends Factory
{
    protected $model = Batch::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $collectionDate = fake()->dateTimeBetween('-7 days', 'now');
        $initialQuantity = fake()->numberBetween(100, 1000);

        return [
            'farm_id' => Farm::factory(),
            'egg_category_id' => EggCategory::factory(),
            'collection_date' => $collectionDate,
            'expires_at' => Carbon::parse($collectionDate)->addDays(28),
            'initial_quantity' => $initialQuantity,
            'current_quantity' => $initialQuantity,
            'status' => 'active',
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the batch is active with specific quantities.
     */
    public function withQuantity(int $initial, ?int $current = null): static
    {
        return $this->state(fn (array $attributes) => [
            'initial_quantity' => $initial,
            'current_quantity' => $current ?? $initial,
        ]);
    }

    /**
     * Indicate that the batch is depleted.
     */
    public function depleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'depleted',
            'current_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the batch is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * Indicate that the batch is expiring soon.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays(2),
        ]);
    }

    /**
     * Indicate that the batch was collected on a specific date.
     */
    public function collectedOn(Carbon $date): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_date' => $date,
            'expires_at' => $date->copy()->addDays(28),
        ]);
    }
}
