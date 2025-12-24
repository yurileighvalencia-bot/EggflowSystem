<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class WastageLog extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    const SOURCE_SHOP_SPOILAGE = 'shop_spoilage';
    const SOURCE_DELIVERY_REJECTION = 'delivery_rejection';
    const SOURCE_BATCH_EXPIRED = 'batch_expired';
    const SOURCE_INVENTORY_ADJUSTMENT = 'inventory_adjustment';
    const SOURCE_OTHER = 'other';

    const SOURCES = [
        self::SOURCE_SHOP_SPOILAGE => 'Shop Spoilage',
        self::SOURCE_DELIVERY_REJECTION => 'Delivery Rejection',
        self::SOURCE_BATCH_EXPIRED => 'Batch Expired',
        self::SOURCE_INVENTORY_ADJUSTMENT => 'Inventory Adjustment',
        self::SOURCE_OTHER => 'Other',
    ];

    protected $fillable = [
        'shop_id',
        'batch_id',
        'delivery_id',
        'egg_category_id',
        'quantity',
        'source',
        'reason',
        'logged_by',
        'logged_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'logged_at' => 'datetime',
    ];

    /**
     * Get the shop where this wastage occurred.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the batch this wastage is from.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the delivery associated with this wastage.
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * Get the egg category for this wastage.
     */
    public function eggCategory(): BelongsTo
    {
        return $this->belongsTo(EggCategory::class);
    }

    /**
     * Get the user who logged this wastage.
     */
    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    /**
     * Create wastage from delivery rejection.
     */
    public static function createFromDeliveryRejection(
        DeliveryItem $item,
        int $loggedBy,
        ?int $shopId = null
    ): self {
        return static::create([
            'shop_id' => $shopId ?? $item->delivery->shop_id,
            'batch_id' => $item->batch_id,
            'delivery_id' => $item->delivery_id,
            'egg_category_id' => $item->egg_category_id,
            'quantity' => $item->qty_rejected,
            'source' => self::SOURCE_DELIVERY_REJECTION,
            'reason' => $item->rejection_reason ?? 'Rejected during delivery',
            'logged_by' => $loggedBy,
            'logged_at' => now(),
        ]);
    }

    /**
     * Create wastage from expired batch.
     */
    public static function createFromExpiredBatch(
        Batch $batch,
        int $quantity,
        int $loggedBy,
        ?int $shopId = null
    ): self {
        return static::create([
            'shop_id' => $shopId,
            'batch_id' => $batch->id,
            'egg_category_id' => $batch->egg_category_id,
            'quantity' => $quantity,
            'source' => self::SOURCE_BATCH_EXPIRED,
            'reason' => 'Batch expired on ' . $batch->expires_at->format('Y-m-d'),
            'logged_by' => $loggedBy,
            'logged_at' => now(),
        ]);
    }

    /**
     * Get all source options.
     */
    public static function getSources(): array
    {
        return [
            self::SOURCE_SHOP_SPOILAGE => 'Shop Spoilage',
            self::SOURCE_DELIVERY_REJECTION => 'Delivery Rejection',
            self::SOURCE_BATCH_EXPIRED => 'Batch Expired',
            self::SOURCE_INVENTORY_ADJUSTMENT => 'Inventory Adjustment',
            self::SOURCE_OTHER => 'Other',
        ];
    }

    /**
     * Scope for wastage by source.
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope for wastage by shop.
     */
    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope for wastage by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('logged_at', [$startDate, $endDate]);
    }

    /**
     * Scope for delivery-related wastage.
     */
    public function scopeFromDeliveries($query)
    {
        return $query->whereNotNull('delivery_id');
    }

    /**
     * Get total wastage by source for a period.
     */
    public static function getTotalsBySource($startDate, $endDate, $shopId = null): array
    {
        $query = static::whereBetween('logged_at', [$startDate, $endDate]);
        
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->selectRaw('source, SUM(quantity) as total')
            ->groupBy('source')
            ->pluck('total', 'source')
            ->toArray();
    }
}
