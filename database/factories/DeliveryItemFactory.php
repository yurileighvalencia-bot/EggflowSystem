<?php

namespace Database\Factories;

use App\Models\DeliveryItem;
use App\Models\Delivery;
use App\Models\Batch;
use App\Models\EggCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryItem>
 */
class DeliveryItemFactory extends Factory
{
    protected $model = DeliveryItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $qtySent = fake()->numberBetween(30, 300);
        
        return [
            'delivery_id' => Delivery::factory(),
            'batch_id' => Batch::factory(),
            'egg_category_id' => EggCategory::factory(),
            'qty_sent' => $qtySent,
            'qty_received' => $qtySent,
            'qty_rejected' => 0,
        ];
    }

    /**
     * Indicate the item has partial rejection.
     */
    public function withRejection(int $rejected = null): static
    {
        return $this->state(function (array $attributes) use ($rejected) {
            $qtySent = $attributes['qty_sent'];
            $qtyRejected = $rejected ?? fake()->numberBetween(1, (int) ($qtySent * 0.2));
            
            return [
                'qty_received' => $qtySent - $qtyRejected,
                'qty_rejected' => $qtyRejected,
            ];
        });
    }

    /**
     * Indicate the item is pending (not yet received).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'qty_received' => 0,
            'qty_rejected' => 0,
        ]);
    }

    /**
     * Set specific quantities.
     */
    public function withQuantities(int $sent, int $received, int $rejected = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'qty_sent' => $sent,
            'qty_received' => $received,
            'qty_rejected' => $rejected,
        ]);
    }
}
