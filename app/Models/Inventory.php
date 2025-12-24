<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Inventory extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'shop_id',
        'egg_category_id',
        'batch_id',
        'available_stock',
        'reserved_stock',
        'unit_price',
    ];

    protected $casts = [
        'available_stock' => 'integer',
        'reserved_stock' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    /**
     * Get the shop this inventory belongs to.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the egg category for this inventory.
     */
    public function eggCategory(): BelongsTo
    {
        return $this->belongsTo(EggCategory::class);
    }

    /**
     * Get the batch for this inventory.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get total stock (available + reserved).
     */
    public function getTotalStockAttribute(): int
    {
        return $this->available_stock + $this->reserved_stock;
    }

    /**
     * Check if stock is below threshold.
     */
    public function isBelowThreshold(): bool
    {
        $threshold = $this->eggCategory->low_stock_threshold ?? 0;
        return $this->available_stock <= $threshold;
    }

    /**
     * Add stock to inventory.
     */
    public function addStock(int $quantity): bool
    {
        $this->available_stock += $quantity;
        return $this->save();
    }

    /**
     * Reserve stock for a customer.
     */
    public function reserveStock(int $quantity): bool
    {
        if ($quantity > $this->available_stock) {
            return false;
        }

        $this->available_stock -= $quantity;
        $this->reserved_stock += $quantity;
        return $this->save();
    }

    /**
     * Release reserved stock back to available.
     */
    public function releaseReservedStock(int $quantity): bool
    {
        if ($quantity > $this->reserved_stock) {
            return false;
        }

        $this->reserved_stock -= $quantity;
        $this->available_stock += $quantity;
        return $this->save();
    }

    /**
     * Fulfill reserved stock (customer picks up).
     */
    public function fulfillReservedStock(int $quantity): bool
    {
        if ($quantity > $this->reserved_stock) {
            return false;
        }

        $this->reserved_stock -= $quantity;
        return $this->save();
    }

    /**
     * Deduct available stock (direct sale).
     */
    public function deductStock(int $quantity): bool
    {
        if ($quantity > $this->available_stock) {
            return false;
        }

        $this->available_stock -= $quantity;
        return $this->save();
    }

    /**
     * Scope for inventory with available stock.
     */
    public function scopeWithAvailableStock($query)
    {
        return $query->where('available_stock', '>', 0);
    }

    /**
     * Scope for low stock inventory.
     */
    public function scopeLowStock($query)
    {
        return $query->whereHas('eggCategory', function ($q) {
            $q->whereColumn('inventories.available_stock', '<=', 'egg_categories.low_stock_threshold');
        });
    }

    /**
     * Scope for inventory by shop.
     */
    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope for FIFO ordering by batch collection date.
     */
    public function scopeFifo($query)
    {
        return $query->join('batches', 'inventories.batch_id', '=', 'batches.id')
            ->orderBy('batches.collection_date', 'asc')
            ->orderBy('batches.id', 'asc')
            ->select('inventories.*');
    }

    /**
     * Get aggregated stock for a category at a shop (across all batches).
     */
    public static function getTotalStockForCategory($shopId, $categoryId): array
    {
        $result = static::where('shop_id', $shopId)
            ->where('egg_category_id', $categoryId)
            ->selectRaw('SUM(available_stock) as available, SUM(reserved_stock) as reserved')
            ->first();

        return [
            'available' => $result->available ?? 0,
            'reserved' => $result->reserved ?? 0,
            'total' => ($result->available ?? 0) + ($result->reserved ?? 0),
        ];
    }
}
