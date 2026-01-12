<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->optional(0.7)->address(),
            'credit_limit' => fake()->randomElement([0, 0, 0, 500, 1000, 2000, 5000]),
            'current_balance' => 0,
            'notes' => fake()->optional(0.2)->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Customer with credit limit.
     */
    public function withCredit(float $limit = 1000): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit' => $limit,
        ]);
    }

    /**
     * Customer with outstanding balance.
     */
    public function withBalance(float $balance = 500): static
    {
        return $this->state(fn (array $attributes) => [
            'current_balance' => $balance,
        ]);
    }

    /**
     * Inactive customer.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Business customer with higher credit.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->company(),
            'credit_limit' => fake()->randomElement([5000, 10000, 20000]),
            'address' => fake()->address(),
        ]);
    }
}
