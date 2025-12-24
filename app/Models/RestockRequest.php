<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable;

class RestockRequest extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    const STATUS_PENDING = 'pending';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_PARTIAL = 'partial';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_DISPUTED = 'disputed';

    protected $fillable = [
        'shop_id',
        'egg_category_id',
        'quantity_requested',
        'quantity_fulfilled',
        'quantity_remaining',
        'status',
        'requested_by',
        'acknowledged_by',
        'acknowledged_at',
        'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'integer',
        'quantity_fulfilled' => 'integer',
        'quantity_remaining' => 'integer',
        'acknowledged_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->quantity_remaining)) {
                $request->quantity_remaining = $request->quantity_requested;
            }
        });
    }

    /**
     * Get the shop that made this request.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the egg category for this request.
     */
    public function eggCategory(): BelongsTo
    {
        return $this->belongsTo(EggCategory::class);
    }

    /**
     * Get the user who requested this restock.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who acknowledged this request.
     */
    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Get the deliveries for this request.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Get the latest delivery.
     */
    public function latestDelivery(): HasOne
    {
        return $this->hasOne(Delivery::class)->latestOfMany();
    }

    /**
     * Acknowledge the restock request.
     */
    public function acknowledge(int $userId): bool
    {
        $this->status = self::STATUS_ACKNOWLEDGED;
        $this->acknowledged_by = $userId;
        $this->acknowledged_at = now();
        return $this->save();
    }

    /**
     * Mark as in transit.
     */
    public function markInTransit(): bool
    {
        $this->status = self::STATUS_IN_TRANSIT;
        return $this->save();
    }

    /**
     * Record fulfilled quantity.
     */
    public function recordFulfillment(int $quantity): bool
    {
        $this->quantity_fulfilled += $quantity;
        $this->quantity_remaining = max(0, $this->quantity_requested - $this->quantity_fulfilled);

        if ($this->quantity_remaining === 0) {
            $this->status = self::STATUS_DELIVERED;
        } elseif ($this->quantity_fulfilled > 0) {
            $this->status = self::STATUS_PARTIAL;
        }

        return $this->save();
    }

    /**
     * Cancel the request.
     */
    public function cancel(): bool
    {
        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }

    /**
     * Mark as disputed.
     */
    public function dispute(): bool
    {
        $this->status = self::STATUS_DISPUTED;
        return $this->save();
    }

    /**
     * Check if request is active (needs fulfillment).
     */
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ACKNOWLEDGED,
            self::STATUS_IN_TRANSIT,
            self::STATUS_PARTIAL,
        ]);
    }

    /**
     * Check if there's an active request for this shop and category.
     */
    public static function hasActiveRequest($shopId, $categoryId): bool
    {
        return static::where('shop_id', $shopId)
            ->where('egg_category_id', $categoryId)
            ->whereIn('status', [
                self::STATUS_PENDING,
                self::STATUS_ACKNOWLEDGED,
                self::STATUS_IN_TRANSIT,
                self::STATUS_PARTIAL,
            ])
            ->exists();
    }

    /**
     * Scope for active requests.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_ACKNOWLEDGED,
            self::STATUS_IN_TRANSIT,
            self::STATUS_PARTIAL,
        ]);
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for requests by shop.
     */
    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }
}
