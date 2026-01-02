<?php

use App\Models\Shop;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Public channel for all stock updates (managers)
Broadcast::channel('stock.all', function ($user) {
    return $user->hasRole('Manager');
});

// Per-shop stock updates channel
Broadcast::channel('stock.{shopId}', function ($user, int $shopId) {
    // Managers can listen to any shop
    if ($user->hasRole('Manager')) {
        return true;
    }
    
    // Shop staff can only listen to their own shop
    return $user->shop_id === $shopId;
});

// Batches channel for expiration events
Broadcast::channel('batches', function ($user) {
    // All authenticated users can receive batch notifications
    return true;
});

// Farm-specific channel
Broadcast::channel('farm.{farmId}', function ($user, int $farmId) {
    // Managers can listen to any farm
    if ($user->hasRole('Manager')) {
        return true;
    }
    
    // Farm staff can only listen to their own farm
    return $user->farm_id === $farmId;
});

// Low stock alerts channel
Broadcast::channel('alerts.low-stock.{shopId}', function ($user, int $shopId) {
    if ($user->hasRole('Manager')) {
        return true;
    }
    
    return $user->shop_id === $shopId;
});

// Deliveries channel for shop staff
Broadcast::channel('deliveries.{shopId}', function ($user, int $shopId) {
    // Managers can listen to any shop's deliveries
    if ($user->hasRole('Manager')) {
        return true;
    }
    
    // Shop staff can only listen to their own shop's deliveries
    return $user->shop_id === $shopId;
});
