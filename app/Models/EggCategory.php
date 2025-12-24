<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class EggCategory extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'code',
        'description',
        'low_stock_threshold',
        'restock_quantity',
        'default_price',
        'sort_order',
        'is_active',
        'is_tax_exempt',
    ];

    protected $casts = [
        'low_stock_threshold' => 'integer',
        'restock_quantity' => 'integer',
        'default_price' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_tax_exempt' => 'boolean',
    ];

    /**
     * Get the batches for this egg category.
     */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /**
     * Get the daily collections for this egg category.
     */
    public function dailyCollections(): HasMany
    {
        return $this->hasMany(DailyCollection::class);
    }

    /**
     * Get the inventory records for this egg category.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the restock requests for this egg category.
     */
    public function restockRequests(): HasMany
    {
        return $this->hasMany(RestockRequest::class);
    }

    /**
     * Scope for active categories only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered categories.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
