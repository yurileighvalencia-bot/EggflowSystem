<?php

namespace Database\Factories;

use App\Models\EggCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EggCategory>
 */
class EggCategoryFactory extends Factory
{
    protected $model = EggCategory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $sizes = ['Small', 'Medium', 'Large', 'Extra Large', 'Jumbo'];
        $types = ['White', 'Brown', 'Organic', 'Free Range', 'Duck'];

        return [
            'name' => fake()->unique()->randomElement($sizes) . ' ' . fake()->randomElement($types) . ' Eggs',
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'low_stock_threshold' => fake()->numberBetween(50, 200),
            'restock_quantity' => fake()->numberBetween(200, 1000),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Define common egg categories.
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Small Eggs',
            'code' => 'SM',
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Medium Eggs',
            'code' => 'MD',
        ]);
    }

    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Large Eggs',
            'code' => 'LG',
        ]);
    }
}
