<?php

namespace Database\Factories;

use App\Models\DeliveryDiscrepancy;
use App\Models\DeliveryItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryDiscrepancy>
 */
class DeliveryDiscrepancyFactory extends Factory
{
    protected $model = DeliveryDiscrepancy::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $qtySent = fake()->numberBetween(50, 200);
        $qtyReceived = fake()->numberBetween(30, $qtySent - 5);
        
        return [
            'delivery_item_id' => DeliveryItem::factory(),
            'qty_sent' => $qtySent,
            'qty_received' => $qtyReceived,
            'qty_missing' => $qtySent - $qtyReceived,
            'reason' => fake()->randomElement([
                'Broken eggs during transport',
                'Miscounted at farm',
                'Partial theft suspected',
                'Packaging issue',
                'Temperature damage',
            ]),
            'reported_by' => User::factory(),
            'reported_at' => now(),
            'status' => DeliveryDiscrepancy::STATUS_REPORTED,
            'investigated_by' => null,
            'investigated_at' => null,
            'investigation_notes' => null,
            'resolution' => null,
        ];
    }

    /**
     * Indicate the discrepancy is under investigation.
     */
    public function investigating(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryDiscrepancy::STATUS_INVESTIGATING,
            'investigated_by' => User::factory(),
            'investigated_at' => now(),
        ]);
    }

    /**
     * Indicate the discrepancy is resolved.
     */
    public function resolved(string $resolution = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryDiscrepancy::STATUS_RESOLVED,
            'investigated_by' => User::factory(),
            'investigated_at' => now()->subHours(2),
            'investigation_notes' => fake()->paragraph(),
            'resolution' => $resolution ?? fake()->randomElement([
                'Eggs confirmed broken - farm credited',
                'Miscount verified - inventory adjusted',
                'Driver error - retraining scheduled',
                'No further action required',
            ]),
        ]);
    }

    /**
     * Create a minor discrepancy.
     */
    public function minor(): static
    {
        return $this->state(function (array $attributes) {
            $qtySent = $attributes['qty_sent'] ?? 100;
            $qtyMissing = fake()->numberBetween(1, 5);
            
            return [
                'qty_sent' => $qtySent,
                'qty_received' => $qtySent - $qtyMissing,
                'qty_missing' => $qtyMissing,
            ];
        });
    }

    /**
     * Create a major discrepancy.
     */
    public function major(): static
    {
        return $this->state(function (array $attributes) {
            $qtySent = $attributes['qty_sent'] ?? 100;
            $qtyMissing = fake()->numberBetween((int) ($qtySent * 0.2), (int) ($qtySent * 0.5));
            
            return [
                'qty_sent' => $qtySent,
                'qty_received' => $qtySent - $qtyMissing,
                'qty_missing' => $qtyMissing,
            ];
        });
    }
}
