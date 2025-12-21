<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class DailyCollection extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'staff_id',
        'batch_id',
        'egg_category_id',
        'farm_id',
        'quantity',
        'collection_date',
        'collection_time',
        'notes',
        'is_verified',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'collection_time' => 'datetime:H:i',
        'quantity' => 'integer',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the staff member who recorded this collection.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /**
     * Get the batch this collection belongs to.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the egg category for this collection.
     */
    public function eggCategory(): BelongsTo
    {
        return $this->belongsTo(EggCategory::class);
    }

    /**
     * Get the farm this collection is from.
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Get the user who verified this collection.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the revision history for this collection.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(DailyCollectionRevision::class);
    }

    /**
     * Create a revision record before updating.
     */
    public function createRevision(array $newValues, string $reason, int $changedBy): DailyCollectionRevision
    {
        return $this->revisions()->create([
            'changed_by' => $changedBy,
            'old_values' => $this->getOriginal(),
            'new_values' => $newValues,
            'reason' => $reason,
            'changed_at' => now(),
        ]);
    }

    /**
     * Verify this collection.
     */
    public function verify(int $verifierId): bool
    {
        $this->is_verified = true;
        $this->verified_by = $verifierId;
        $this->verified_at = now();
        return $this->save();
    }

    /**
     * Scope for collections by date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('collection_date', $date);
    }

    /**
     * Scope for collections by staff member.
     */
    public function scopeByStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    /**
     * Scope for verified collections.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for unverified collections.
     */
    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    /**
     * Get total collection for a specific date and category.
     */
    public static function getTotalForDateAndCategory($date, $categoryId, $farmId = null)
    {
        $query = static::whereDate('collection_date', $date)
            ->where('egg_category_id', $categoryId);
        
        if ($farmId) {
            $query->where('farm_id', $farmId);
        }
        
        return $query->sum('quantity');
    }
}
