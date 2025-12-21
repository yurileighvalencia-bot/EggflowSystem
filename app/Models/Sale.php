<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Sale extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    const STATUS_COMPLETED = 'completed';
    const STATUS_VOIDED = 'voided';
    const STATUS_REFUNDED = 'refunded';

    const PAYMENT_CASH = 'cash';
    const PAYMENT_CARD = 'card';
    const PAYMENT_TRANSFER = 'transfer';
    const PAYMENT_OTHER = 'other';

    const PAYMENT_METHODS = [
        self::PAYMENT_CASH,
        self::PAYMENT_CARD,
        self::PAYMENT_TRANSFER,
        self::PAYMENT_OTHER,
    ];

    protected $fillable = [
        'shop_id',
        'customer_id',
        'staff_id',
        'reservation_id',
        'sale_code',
        'subtotal',
        'tax',
        'discount',
        'total',
        'payment_method',
        'status',
        'sold_at',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->sale_code)) {
                $sale->sale_code = static::generateCode();
            }
            if (empty($sale->sold_at)) {
                $sale->sold_at = now();
            }
        });
    }

    public static function generateCode(): string
    {
        $date = now()->format('Ymd');
        $count = static::whereDate('sold_at', today())->count() + 1;
        return sprintf('SAL-%s-%04d', $date, $count);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function void(): bool
    {
        $this->status = self::STATUS_VOIDED;
        return $this->save();
    }

    public function refund(): bool
    {
        $this->status = self::STATUS_REFUNDED;
        return $this->save();
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('line_total');
        $this->total = $this->subtotal + $this->tax - $this->discount;
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('sold_at', $date);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('sold_at', [$startDate, $endDate]);
    }

    public static function getPaymentMethods(): array
    {
        return [
            self::PAYMENT_CASH => 'Cash',
            self::PAYMENT_CARD => 'Card',
            self::PAYMENT_TRANSFER => 'Bank Transfer',
            self::PAYMENT_OTHER => 'Other',
        ];
    }
}
