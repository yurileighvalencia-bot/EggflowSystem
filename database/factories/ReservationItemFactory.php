<?php

namespace Database\Factories;

use App\Models\ReservationItem;
use App\Models\Reservation;
use App\Models\EggCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReservationItem>
 */
class ReservationItemFactory extends Factory
{
    protected $model = ReservationItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(6, 60);
        $unitPrice = fake()->randomFloat(2, 5, 15);
        
        return [
            'reservation_id' => Reservation::factory(),
            'egg_category_id' => EggCategory::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $quantity * $unitPrice,
        ];
    }

    /**
     * Set a specific quantity and price.
     */
    public function withDetails(int $quantity, float $unitPrice): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $quantity * $unitPrice,
        ]);
    }

    /**
     * Create a small order item.
     */
    public function small(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->numberBetween(6, 12);
            $unitPrice = $attributes['unit_price'] ?? fake()->randomFloat(2, 5, 15);
            
            return [
                'quantity' => $quantity,
                'subtotal' => $quantity * $unitPrice,
            ];
        });
    }

    /**
     * Create a large order item.
     */
    public function large(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->numberBetween(100, 500);
            $unitPrice = $attributes['unit_price'] ?? fake()->randomFloat(2, 5, 15);
            
            return [
                'quantity' => $quantity,
                'subtotal' => $quantity * $unitPrice,
            ];
        });
    }
}
