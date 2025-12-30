<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements Auditable, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'farm_id',
        'shop_id',
        'name',
        'email',
        'password',
        'phone',
        'address',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the farm this user belongs to (for farm staff).
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Get the shop this user belongs to (for shop staff).
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the daily collections recorded by this user.
     */
    public function dailyCollections(): HasMany
    {
        return $this->hasMany(DailyCollection::class, 'staff_id');
    }

    /**
     * Get the reservations made by this customer.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'customer_id');
    }

    /**
     * Get the sales processed by this staff member.
     */
    public function salesProcessed(): HasMany
    {
        return $this->hasMany(Sale::class, 'staff_id');
    }

    /**
     * Get the sales made to this customer.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    /**
     * Get the batches created by this user.
     */
    public function batchesCreated(): HasMany
    {
        return $this->hasMany(Batch::class, 'created_by');
    }

    /**
     * Get the daily collections verified by this user.
     */
    public function verificationsPerformed(): HasMany
    {
        return $this->hasMany(DailyCollection::class, 'verified_by');
    }

    /**
     * Get the deliveries dispatched by this user.
     */
    public function dispatchedDeliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'dispatched_by');
    }

    /**
     * Get the deliveries received by this user.
     */
    public function receivedDeliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'received_by');
    }

    /**
     * Get the discrepancies reported by this user.
     */
    public function reportedDiscrepancies(): HasMany
    {
        return $this->hasMany(DeliveryDiscrepancy::class, 'reported_by');
    }

    /**
     * Get the discrepancies investigated by this user.
     */
    public function investigatedDiscrepancies(): HasMany
    {
        return $this->hasMany(DeliveryDiscrepancy::class, 'investigated_by');
    }

    /**
     * Get the reservations cancelled by this user (staff).
     */
    public function cancelledReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'cancelled_by');
    }

    /**
     * Get the restock requests created by this user.
     */
    public function restockRequestsMade(): HasMany
    {
        return $this->hasMany(RestockRequest::class, 'requested_by');
    }

    /**
     * Get the restock requests acknowledged by this user.
     */
    public function restockRequestsAcknowledged(): HasMany
    {
        return $this->hasMany(RestockRequest::class, 'acknowledged_by');
    }

    /**
     * Get the wastage logs recorded by this user.
     */
    public function loggedWastage(): HasMany
    {
        return $this->hasMany(WastageLog::class, 'logged_by');
    }

    /**
     * Get the daily collection revisions performed by this user.
     */
    public function collectionRevisionsPerformed(): HasMany
    {
        return $this->hasMany(DailyCollectionRevision::class, 'changed_by');
    }

    /**
     * Check if user is a farm staff member.
     */
    public function isFarmStaff(): bool
    {
        return $this->hasRole('farm_staff');
    }

    /**
     * Check if user is a shop staff member.
     */
    public function isShopStaff(): bool
    {
        return $this->hasRole('shop_staff');
    }

    /**
     * Check if user is a manager.
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    /**
     * Check if user is a customer.
     */
    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }

    /**
     * Scope for active users only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
