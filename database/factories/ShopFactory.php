<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shop>
 */
class ShopFactory extends Factory
{
    protected $model = Shop::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'name' => fake()->company() . ' Egg Shop',
            'address' => fake()->streetAddress(),
        ];
    }

    /**
     * Indicate that the shop belongs to a specific farm.
     */
    public function forFarm(Farm $farm): static
    {
        return $this->state(fn (array $attributes) => [
            'farm_id' => $farm->id,
        ]);
    }
}
