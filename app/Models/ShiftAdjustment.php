<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class ShiftAdjustment extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    const TYPE_CASH_IN = 'cash_in';
    const TYPE_CASH_OUT = 'cash_out';

    const TYPES = [
        self::TYPE_CASH_IN => 'Cash In',
        self::TYPE_CASH_OUT => 'Cash Out',
    ];

    protected $fillable = [
        'shift_id',
        'user_id',
        'type',
        'amount',
        'reason',
        'adjusted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'adjusted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($adjustment) {
            if (empty($adjustment->adjusted_at)) {
                $adjustment->adjusted_at = now();
            }
        });
    }

    /**
     * Get the shift this adjustment belongs to.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the user who made this adjustment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this is a cash-in adjustment.
     */
    public function isCashIn(): bool
    {
        return $this->type === self::TYPE_CASH_IN;
    }

    /**
     * Check if this is a cash-out adjustment.
     */
    public function isCashOut(): bool
    {
        return $this->type === self::TYPE_CASH_OUT;
    }

    /**
     * Scope to get cash-in adjustments.
     */
    public function scopeCashIn($query)
    {
        return $query->where('type', self::TYPE_CASH_IN);
    }

    /**
     * Scope to get cash-out adjustments.
     */
    public function scopeCashOut($query)
    {
        return $query->where('type', self::TYPE_CASH_OUT);
    }
}
