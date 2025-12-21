<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'is_active' => true,
            'farm_id' => null,
            'shop_id' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Assign the user to a farm (for farm staff).
     */
    public function forFarm(Farm $farm = null): static
    {
        return $this->state(fn (array $attributes) => [
            'farm_id' => $farm?->id ?? Farm::factory(),
            'shop_id' => null,
        ]);
    }

    /**
     * Assign the user to a shop (for shop staff).
     */
    public function forShop(Shop $shop = null): static
    {
        return $this->state(fn (array $attributes) => [
            'shop_id' => $shop?->id ?? Shop::factory(),
            'farm_id' => null,
        ]);
    }

    /**
     * Create a manager user.
     */
    public function manager(): static
    {
        return $this->afterCreating(function ($user) {
            $user->assignRole('manager');
        });
    }

    /**
     * Create a farm staff user.
     */
    public function farmStaff(Farm $farm = null): static
    {
        return $this->forFarm($farm)->afterCreating(function ($user) {
            $user->assignRole('farm_staff');
        });
    }

    /**
     * Create a shop staff user.
     */
    public function shopStaff(Shop $shop = null): static
    {
        return $this->forShop($shop)->afterCreating(function ($user) {
            $user->assignRole('shop_staff');
        });
    }

    /**
     * Create a customer user.
     */
    public function customer(): static
    {
        return $this->afterCreating(function ($user) {
            $user->assignRole('customer');
        });
    }
}
