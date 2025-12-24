<?php

namespace Database\Factories;

use App\Models\SaleItem;
use App\Models\Sale;
use App\Models\Batch;
use App\Models\EggCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SaleItem>
 */
class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(6, 60);
        $unitPrice = fake()->randomFloat(2, 5, 15);
        
        return [
            'sale_id' => Sale::factory(),
            'batch_id' => Batch::factory(),
            'egg_category_id' => EggCategory::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $quantity * $unitPrice,
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
            'line_total' => $quantity * $unitPrice,
        ]);
    }

    /**
     * Create a small purchase item.
     */
    public function small(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->numberBetween(6, 12);
            $unitPrice = $attributes['unit_price'] ?? fake()->randomFloat(2, 5, 15);
            
            return [
                'quantity' => $quantity,
                'line_total' => $quantity * $unitPrice,
            ];
        });
    }

    /**
     * Create a bulk purchase item.
     */
    public function bulk(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = fake()->numberBetween(100, 500);
            $unitPrice = $attributes['unit_price'] ?? fake()->randomFloat(2, 5, 15);
            
            return [
                'quantity' => $quantity,
                'line_total' => $quantity * $unitPrice,
            ];
        });
    }

    /**
     * Use a batch that has sufficient stock.
     * This ensures test data reflects real-world constraints where
     * stock deductions happen via InventoryService, not factory creation.
     * 
     * WARNING: This does NOT deduct stock - use InventoryService in tests
     * for realistic FIFO stock management.
     */
    public function forBatchWithStock(Batch $batch, ?int $quantity = null): static
    {
        return $this->state(function (array $attributes) use ($batch, $quantity) {
            $qty = $quantity ?? $attributes['quantity'] ?? fake()->numberBetween(6, 60);
            $unitPrice = $attributes['unit_price'] ?? fake()->randomFloat(2, 5, 15);
            
            // Ensure we don't exceed batch's current stock
            $qty = min($qty, $batch->current_quantity);
            
            return [
                'batch_id' => $batch->id,
                'egg_category_id' => $batch->egg_category_id,
                'quantity' => $qty,
                'line_total' => $qty * $unitPrice,
            ];
        });
    }
}
