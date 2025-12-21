<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class DeliveryDiscrepancy extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    const RESOLUTION_APPROVED = 'approved';
    const RESOLUTION_REJECTED = 'rejected';
    const RESOLUTION_PARTIAL_LOSS = 'partial_loss';
    const RESOLUTION_OTHER = 'other';

    protected $fillable = [
        'delivery_id',
        'delivery_item_id',
        'qty_sent',
        'qty_received',
        'qty_rejected',
        'qty_missing',
        'reported_by',
        'investigated_by',
        'resolution',
        'notes',
        'reported_at',
        'investigated_at',
    ];

    protected $casts = [
        'qty_sent' => 'integer',
        'qty_received' => 'integer',
        'qty_rejected' => 'integer',
        'qty_missing' => 'integer',
        'reported_at' => 'datetime',
        'investigated_at' => 'datetime',
    ];

    /**
     * Get the delivery this discrepancy belongs to.
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * Get the specific delivery item (if applicable).
     */
    public function deliveryItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryItem::class);
    }

    /**
     * Get the user who reported this discrepancy.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Get the user who investigated this discrepancy.
     */
    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigated_by');
    }

    /**
     * Check if this discrepancy is resolved.
     */
    public function isResolved(): bool
    {
        return $this->resolution !== null;
    }

    /**
     * Investigate and resolve the discrepancy.
     */
    public function investigate(int $userId, string $resolution, ?string $notes = null): bool
    {
        $this->investigated_by = $userId;
        $this->investigated_at = now();
        $this->resolution = $resolution;
        
        if ($notes) {
            $this->notes = $notes;
        }

        return $this->save();
    }

    /**
     * Create a discrepancy from a delivery item.
     */
    public static function createFromItem(DeliveryItem $item, int $reportedBy): self
    {
        return static::create([
            'delivery_id' => $item->delivery_id,
            'delivery_item_id' => $item->id,
            'qty_sent' => $item->qty_sent,
            'qty_received' => $item->qty_received,
            'qty_rejected' => $item->qty_rejected,
            'qty_missing' => $item->missing_quantity,
            'reported_by' => $reportedBy,
            'reported_at' => now(),
        ]);
    }

    /**
     * Get all resolution options.
     */
    public static function getResolutions(): array
    {
        return [
            self::RESOLUTION_APPROVED => 'Approved as valid loss',
            self::RESOLUTION_REJECTED => 'Rejected - no loss found',
            self::RESOLUTION_PARTIAL_LOSS => 'Partial loss confirmed',
            self::RESOLUTION_OTHER => 'Other',
        ];
    }

    /**
     * Scope for unresolved discrepancies.
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolution');
    }

    /**
     * Scope for resolved discrepancies.
     */
    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolution');
    }
}
