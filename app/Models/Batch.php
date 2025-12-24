<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;
use Carbon\Carbon;

class Batch extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'farm_id',
        'egg_category_id',
        'batch_code',
        'collection_date',
        'expires_at',
        'initial_quantity',
        'current_quantity',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'expires_at' => 'date',
        'initial_quantity' => 'integer',
        'current_quantity' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($batch) {
            if (empty($batch->batch_code)) {
                $batch->batch_code = static::generateBatchCode($batch);
            }
            if (empty($batch->expires_at)) {
                // Default shelf life: 28 days from collection
                $batch->expires_at = Carbon::parse($batch->collection_date)->addDays(28);
            }
        });
    }

    /**
     * Generate a unique batch code.
     */
    public static function generateBatchCode($batch): string
    {
        $farm = $batch->farm;
        $category = $batch->eggCategory;
        $date = Carbon::parse($batch->collection_date)->format('Ymd');
        
        $farmCode = $farm ? strtoupper(substr($farm->name, 0, 3)) : 'FRM';
        $categoryCode = $category ? $category->code : 'EGG';
        
        // Get the count of batches for this date and category
        $count = static::where('egg_category_id', $batch->egg_category_id)
            ->whereDate('collection_date', $batch->collection_date)
            ->count() + 1;
        
        return sprintf('%s-%s-%s-%03d', $farmCode, $categoryCode, $date, $count);
    }

    /**
     * Get the farm that produced this batch.
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Get the user who created this batch.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the egg category of this batch.
     */
    public function eggCategory(): BelongsTo
    {
        return $this->belongsTo(EggCategory::class);
    }

    /**
     * Get the daily collections for this batch.
     */
    public function dailyCollections(): HasMany
    {
        return $this->hasMany(DailyCollection::class);
    }

    /**
     * Get the inventory records for this batch.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Check if batch is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if batch is expiring soon (within 3 days).
     */
    public function isExpiringSoon(): bool
    {
        return $this->expires_at && $this->expires_at->between(now(), now()->addDays(3));
    }

    /**
     * Check if batch has available stock.
     */
    public function hasStock(): bool
    {
        return $this->current_quantity > 0 && $this->status === 'active';
    }

    /**
     * Deduct quantity from batch.
     */
    public function deduct(int $quantity): bool
    {
        if ($quantity > $this->current_quantity) {
            return false;
        }

        $this->current_quantity -= $quantity;
        
        if ($this->current_quantity === 0) {
            $this->status = 'depleted';
        }
        
        return $this->save();
    }

    /**
     * Scope for active batches only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for batches with stock.
     */
    public function scopeWithStock($query)
    {
        return $query->where('current_quantity', '>', 0);
    }

    /**
     * Scope for FIFO ordering (oldest first).
     */
    public function scopeFifo($query)
    {
        return $query->orderBy('collection_date', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Scope for expiring soon batches.
     */
    public function scopeExpiringSoon($query, int $days = 3)
    {
        return $query->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    /**
     * Scope for expired batches.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }
}
