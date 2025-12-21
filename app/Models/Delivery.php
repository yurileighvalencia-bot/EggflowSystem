<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Delivery extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    const STATUS_DISPATCHED = 'dispatched';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_RECEIVED = 'received';
    const STATUS_PARTIAL = 'partial';
    const STATUS_DISPUTED = 'disputed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'restock_request_id',
        'shop_id',
        'dispatched_by',
        'received_by',
        'status',
        'dispatched_at',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    /**
     * Get the restock request this delivery fulfills.
     */
    public function restockRequest(): BelongsTo
    {
        return $this->belongsTo(RestockRequest::class);
    }

    /**
     * Get the shop receiving this delivery.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the user who dispatched this delivery.
     */
    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    /**
     * Get the user who received this delivery.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get the items in this delivery.
     */
    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    /**
     * Get the discrepancies for this delivery.
     */
    public function discrepancies(): HasMany
    {
        return $this->hasMany(DeliveryDiscrepancy::class);
    }

    /**
     * Get the wastage logs related to this delivery.
     */
    public function wastageLogs(): HasMany
    {
        return $this->hasMany(WastageLog::class);
    }

    /**
     * Get total quantity sent.
     */
    public function getTotalSentAttribute(): int
    {
        return $this->items->sum('qty_sent');
    }

    /**
     * Get total quantity received.
     */
    public function getTotalReceivedAttribute(): int
    {
        return $this->items->sum('qty_received') ?? 0;
    }

    /**
     * Get total quantity rejected.
     */
    public function getTotalRejectedAttribute(): int
    {
        return $this->items->sum('qty_rejected');
    }

    /**
     * Check if delivery has discrepancies.
     */
    public function hasDiscrepancies(): bool
    {
        return $this->discrepancies()->exists();
    }

    /**
     * Confirm receipt of delivery.
     */
    public function confirmReceipt(int $userId): bool
    {
        $this->status = self::STATUS_RECEIVED;
        $this->received_by = $userId;
        $this->received_at = now();
        return $this->save();
    }

    /**
     * Mark as partial receipt.
     */
    public function markPartial(int $userId): bool
    {
        $this->status = self::STATUS_PARTIAL;
        $this->received_by = $userId;
        $this->received_at = now();
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
     * Check if all items are confirmed.
     */
    public function isFullyConfirmed(): bool
    {
        return $this->items()->whereNull('qty_received')->doesntExist();
    }

    /**
     * Scope for pending confirmation.
     */
    public function scopePendingConfirmation($query)
    {
        return $query->whereIn('status', [self::STATUS_DISPATCHED, self::STATUS_IN_TRANSIT]);
    }

    /**
     * Scope for deliveries by shop.
     */
    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }
}
