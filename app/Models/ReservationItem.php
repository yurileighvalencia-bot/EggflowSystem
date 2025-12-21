<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationItem extends Model
{
    protected $fillable = [
        'reservation_id',
        'egg_category_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->line_total)) {
                $item->line_total = $item->quantity * $item->unit_price;
            }
        });

        static::updating(function ($item) {
            $item->line_total = $item->quantity * $item->unit_price;
        });
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function eggCategory(): BelongsTo
    {
        return $this->belongsTo(EggCategory::class);
    }
}
