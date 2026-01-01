<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Shift extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';

    const STATUSES = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_CLOSED => 'Closed',
    ];

    protected $fillable = [
        'user_id',
        'shop_id',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'discrepancy',
        'status',
        'notes',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'discrepancy' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shift) {
            if (empty($shift->opened_at)) {
                $shift->opened_at = now();
            }
        });
    }

    /**
     * Get the user who owns this shift.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shop where this shift is.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the adjustments for this shift.
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(ShiftAdjustment::class);
    }

    /**
     * Get the sales made during this shift.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Check if the shift is open.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if the shift is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Calculate expected cash based on blind close formula.
     * expected = opening_cash + SUM(cash sales) + SUM(cash_in adjustments) - SUM(cash_out adjustments)
     */
    public function calculateExpectedCash(): float
    {
        $cashSales = $this->sales()
            ->where('payment_method', Sale::PAYMENT_CASH)
            ->where('status', Sale::STATUS_COMPLETED)
            ->sum('total');

        $cashIn = $this->adjustments()
            ->where('type', ShiftAdjustment::TYPE_CASH_IN)
            ->sum('amount');

        $cashOut = $this->adjustments()
            ->where('type', ShiftAdjustment::TYPE_CASH_OUT)
            ->sum('amount');

        return (float) $this->opening_cash + (float) $cashSales + (float) $cashIn - (float) $cashOut;
    }

    /**
     * Calculate and set discrepancy when closing shift.
     */
    public function calculateDiscrepancy(): float
    {
        $this->expected_cash = $this->calculateExpectedCash();
        return (float) $this->closing_cash - $this->expected_cash;
    }

    /**
     * Close the shift with the given closing cash amount.
     */
    public function close(float $closingCash, ?string $notes = null): self
    {
        $this->closing_cash = $closingCash;
        $this->expected_cash = $this->calculateExpectedCash();
        $this->discrepancy = $closingCash - $this->expected_cash;
        $this->status = self::STATUS_CLOSED;
        $this->closed_at = now();

        if ($notes) {
            $this->notes = $notes;
        }

        $this->save();

        return $this;
    }

    /**
     * Get total cash in adjustments.
     */
    public function getTotalCashInAttribute(): float
    {
        return (float) $this->adjustments()
            ->where('type', ShiftAdjustment::TYPE_CASH_IN)
            ->sum('amount');
    }

    /**
     * Get total cash out adjustments.
     */
    public function getTotalCashOutAttribute(): float
    {
        return (float) $this->adjustments()
            ->where('type', ShiftAdjustment::TYPE_CASH_OUT)
            ->sum('amount');
    }

    /**
     * Get total cash sales.
     */
    public function getTotalCashSalesAttribute(): float
    {
        return (float) $this->sales()
            ->where('payment_method', Sale::PAYMENT_CASH)
            ->where('status', Sale::STATUS_COMPLETED)
            ->sum('total');
    }

    /**
     * Scope to get open shifts.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope to get closed shifts.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Scope to get shifts with discrepancies.
     */
    public function scopeWithDiscrepancy($query)
    {
        return $query->where('status', self::STATUS_CLOSED)
            ->whereNotNull('discrepancy')
            ->where('discrepancy', '!=', 0);
    }

    /**
     * Scope to get today's shifts.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('opened_at', today());
    }
}
