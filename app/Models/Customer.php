<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Customer extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'credit_limit',
        'current_balance',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the customer's sales.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the customer's reservations.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Scope for active customers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to search by name or phone.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }

    /**
     * Get the customer's total purchase amount.
     */
    public function getTotalPurchasesAttribute(): float
    {
        return $this->sales()
            ->where('status', Sale::STATUS_COMPLETED)
            ->sum('total');
    }

    /**
     * Get the customer's purchase count.
     */
    public function getPurchaseCountAttribute(): int
    {
        return $this->sales()
            ->where('status', Sale::STATUS_COMPLETED)
            ->count();
    }

    /**
     * Check if customer has available credit.
     */
    public function hasAvailableCredit(): bool
    {
        return $this->credit_limit > 0 && $this->current_balance < $this->credit_limit;
    }

    /**
     * Get available credit amount.
     */
    public function getAvailableCreditAttribute(): float
    {
        if ($this->credit_limit <= 0) {
            return 0;
        }
        return max(0, $this->credit_limit - $this->current_balance);
    }

    /**
     * Get display name with phone.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->phone) {
            return "{$this->name} ({$this->phone})";
        }
        return $this->name;
    }
}
