<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 2000);
        $tax = $subtotal * 0.12; // 12% VAT

        return [
            'shop_id' => Shop::factory(),
            'customer_id' => fake()->boolean(70) ? User::factory() : null,
            'staff_id' => User::factory(),
            'reservation_id' => null,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
            'sold_at' => now(),
        ];
    }

    /**
     * Indicate that the sale is from a reservation.
     */
    public function fromReservation(Reservation $reservation): static
    {
        return $this->state(fn (array $attributes) => [
            'reservation_id' => $reservation->id,
            'customer_id' => $reservation->customer_id,
            'shop_id' => $reservation->shop_id,
        ]);
    }

    /**
     * Indicate that the sale is a walk-in (no customer).
     */
    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => null,
        ]);
    }

    /**
     * Set specific totals for the sale.
     */
    public function withTotals(float $subtotal, float $tax = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
        ]);
    }
}
