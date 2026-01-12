<?php

namespace App\Models;

use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable;
use Carbon\Carbon;

class Reservation extends Model implements Auditable
{
    use HasFactory, SoftDeletes, CascadeSoftDeletes;
    use \OwenIt\Auditing\Auditable;

    /**
     * Relationships to cascade soft deletes.
     */
    protected array $cascadeDeletes = ['items'];

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_READY = 'ready';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'shop_id',
        'customer_id',
        'reservation_code',
        'status',
        'subtotal',
        'tax',
        'total',
        'pickup_date',
        'pickup_time',
        'pickup_deadline',
        'expires_at',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'expired_at',
        'reminder_sent_at',
        'notes',
        'cancellation_reason',
        'cancelled_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'pickup_date' => 'date',
        'pickup_time' => 'datetime:H:i',
        'pickup_deadline' => 'datetime',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            if (empty($reservation->reservation_code)) {
                $reservation->reservation_code = static::generateCode();
            }
            if (empty($reservation->expires_at)) {
                // Default: expires 24 hours before pickup
                $reservation->expires_at = Carbon::parse($reservation->pickup_date)->subDay();
            }
        });
    }

    public static function generateCode(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        return "RES-{$date}-{$random}";
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the customer who made the reservation.
     * NULL for anonymous reservations.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    public function confirm(): bool
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->confirmed_at = now();
        return $this->save();
    }

    public function markReady(): bool
    {
        $this->status = self::STATUS_READY;
        return $this->save();
    }

    public function complete(): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        return $this->save();
    }

    public function cancel(?int $cancelledBy = null, ?string $reason = null): bool
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_at = now();
        $this->cancelled_by = $cancelledBy;
        $this->cancellation_reason = $reason;
        return $this->save();
    }

    public function expire(): bool
    {
        $this->status = self::STATUS_EXPIRED;
        $this->expired_at = now();
        return $this->save();
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_READY,
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('line_total');
        $this->tax = $this->calculateTax();
        $this->total = $this->subtotal + $this->tax;
    }

    /**
     * Calculate tax based on shop rate and category exemptions.
     * Philippine TRAIN Law: Agricultural products are VAT-exempt.
     */
    protected function calculateTax(): float
    {
        $shop = $this->shop;
        if (!$shop || $shop->default_tax_rate <= 0) {
            return 0;
        }

        $taxableTotal = 0;
        foreach ($this->items as $item) {
            $category = $item->eggCategory;
            if ($category && !$category->is_tax_exempt) {
                $taxableTotal += $item->line_total;
            }
        }

        return round($taxableTotal * $shop->default_tax_rate, 2);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_READY,
        ]);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeExpiringSoon($query, int $hours = 24)
    {
        return $query->whereBetween('expires_at', [now(), now()->addHours($hours)]);
    }
}
