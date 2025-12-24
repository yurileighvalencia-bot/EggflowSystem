<?php

namespace Database\Factories;

use App\Models\DailyCollection;
use App\Models\Farm;
use App\Models\EggCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DailyCollection>
 */
class DailyCollectionFactory extends Factory
{
    protected $model = DailyCollection::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'egg_category_id' => EggCategory::factory(),
            'batch_id' => null,
            'collection_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'quantity' => fake()->numberBetween(50, 500),
            'notes' => fake()->optional(0.3)->sentence(),
            'staff_id' => User::factory(),
        ];
    }

    /**
     * Create a collection for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Create a collection with a specific quantity.
     */
    public function withQuantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Create a collection with notes.
     */
    public function withNotes(string $notes = null): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes ?? fake()->paragraph(),
        ]);
    }
}
