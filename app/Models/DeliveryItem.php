<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class DeliveryItem extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    const REJECTION_CRACKED = 'cracked';
    const REJECTION_SPOILED = 'spoiled';
    const REJECTION_WRONG_SIZE = 'wrong_size';
    const REJECTION_OTHER = 'other';

    protected $fillable = [
        'delivery_id',
        'batch_id',
        'egg_category_id',
        'qty_sent',
        'qty_received',
        'qty_rejected',
        'rejection_reason',
    ];

    protected $casts = [
        'qty_sent' => 'integer',
        'qty_received' => 'integer',
        'qty_rejected' => 'integer',
    ];

    /**
     * Get the delivery this item belongs to.
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * Get the batch for this item.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the egg category for this item.
     */
    public function eggCategory(): BelongsTo
    {
        return $this->belongsTo(EggCategory::class);
    }

    /**
     * Get the discrepancies for this delivery item.
     */
    public function discrepancies(): HasMany
    {
        return $this->hasMany(DeliveryDiscrepancy::class);
    }

    /**
     * Get the missing quantity (unaccounted for).
     */
    public function getMissingQuantityAttribute(): int
    {
        if ($this->qty_received === null) {
            return 0; // Not yet confirmed
        }
        return max(0, $this->qty_sent - $this->qty_received - $this->qty_rejected);
    }

    /**
     * Check if this item has a discrepancy.
     */
    public function hasDiscrepancy(): bool
    {
        return $this->missing_quantity > 0;
    }

    /**
     * Check if this item is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->qty_received !== null;
    }

    /**
     * Confirm receipt of this item.
     */
    public function confirmReceipt(int $received, int $rejected = 0, ?string $reason = null): bool
    {
        $this->qty_received = $received;
        $this->qty_rejected = $rejected;
        
        if ($rejected > 0 && $reason) {
            $this->rejection_reason = $reason;
        }

        return $this->save();
    }

    /**
     * Get all rejection reason options.
     */
    public static function getRejectionReasons(): array
    {
        return [
            self::REJECTION_CRACKED => 'Cracked during transit',
            self::REJECTION_SPOILED => 'Spoiled/Rotten',
            self::REJECTION_WRONG_SIZE => 'Wrong size category',
            self::REJECTION_OTHER => 'Other',
        ];
    }

    /**
     * Scope for items with discrepancies.
     */
    public function scopeWithDiscrepancy($query)
    {
        return $query->whereNotNull('qty_received')
            ->whereRaw('qty_sent > (qty_received + qty_rejected)');
    }

    /**
     * Scope for unconfirmed items.
     */
    public function scopeUnconfirmed($query)
    {
        return $query->whereNull('qty_received');
    }
}
