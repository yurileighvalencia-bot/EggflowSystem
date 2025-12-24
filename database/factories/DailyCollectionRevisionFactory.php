<?php

namespace Database\Factories;

use App\Models\DailyCollectionRevision;
use App\Models\DailyCollection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DailyCollectionRevision>
 */
class DailyCollectionRevisionFactory extends Factory
{
    protected $model = DailyCollectionRevision::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $oldQuantity = fake()->numberBetween(100, 400);
        $newQuantity = max(0, $oldQuantity + fake()->numberBetween(-50, 50));
        
        return [
            'daily_collection_id' => DailyCollection::factory(),
            'old_values' => ['quantity' => $oldQuantity],
            'new_values' => ['quantity' => $newQuantity],
            'reason' => fake()->randomElement([
                'Recount after quality inspection',
                'Data entry error correction',
                'Found additional eggs in storage',
                'Manager review adjustment',
                'Damaged eggs removed from count',
            ]),
            'changed_by' => User::factory(),
            'changed_at' => now(),
        ];
    }

    /**
     * Create an upward revision.
     */
    public function increase(): static
    {
        return $this->state(function (array $attributes) {
            $oldQuantity = $attributes['old_values']['quantity'] ?? fake()->numberBetween(100, 300);
            $increase = fake()->numberBetween(10, 50);
            
            return [
                'old_values' => ['quantity' => $oldQuantity],
                'new_values' => ['quantity' => $oldQuantity + $increase],
                'reason' => 'Found additional eggs during recount',
            ];
        });
    }

    /**
     * Create a downward revision.
     */
    public function decrease(): static
    {
        return $this->state(function (array $attributes) {
            $oldQuantity = $attributes['old_values']['quantity'] ?? fake()->numberBetween(100, 300);
            $decrease = fake()->numberBetween(10, 50);
            
            return [
                'old_values' => ['quantity' => $oldQuantity],
                'new_values' => ['quantity' => max(0, $oldQuantity - $decrease)],
                'reason' => 'Damaged eggs removed from count',
            ];
        });
    }

    /**
     * Create an error correction revision.
     */
    public function errorCorrection(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'Data entry error correction',
        ]);
    }
}
