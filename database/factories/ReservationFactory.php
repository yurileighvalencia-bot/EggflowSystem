<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'customer_id' => User::factory(),
            'status' => Reservation::STATUS_PENDING,
            'pickup_date' => now()->addDays(fake()->numberBetween(1, 3))->toDateString(),
            'expires_at' => now()->addDays(3),
        ];
    }

    /**
     * Indicate that the reservation is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Reservation::STATUS_CONFIRMED,
        ]);
    }

    /**
     * Indicate that the reservation is ready for pickup.
     */
    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Reservation::STATUS_READY,
        ]);
    }

    /**
     * Indicate that the reservation is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Reservation::STATUS_COMPLETED,
        ]);
    }

    /**
     * Indicate that the reservation is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Reservation::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Indicate that the reservation is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Reservation::STATUS_EXPIRED,
            'expires_at' => now()->subDay(),
        ]);
    }
}
