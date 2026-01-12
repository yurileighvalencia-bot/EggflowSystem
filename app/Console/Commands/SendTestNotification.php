<?php

namespace App\Console\Commands;

use App\Models\EggCategory;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\DatabaseNotification;

class SendTestNotification extends Command
{
    protected $signature = 'notification:test {--user= : User ID} {--check : Just check notifications}';
    protected $description = 'Send a test notification';

    public function handle(): int
    {
        $userId = $this->option('user');
        $user = $userId ? User::find($userId) : User::first();
        
        if (!$user) {
            $this->error('No user found');
            return 1;
        }
        
        if ($this->option('check')) {
            $count = DB::table('notifications')->count();
            $this->info("Total notifications in database: {$count}");
            $userNotifs = $user->unreadNotifications()->count();
            $this->info("Unread notifications for {$user->name}: {$userNotifs}");
            return 0;
        }
        
        // Create notification directly in database (synchronously)
        DB::table('notifications')->insert([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'type' => 'App\\Notifications\\LowStockAlertNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'type' => 'low_stock_alert',
                'shop_id' => 1,
                'category_id' => 1,
                'category_name' => 'Small',
                'current_stock' => 10,
                'threshold' => 50,
                'message' => 'Low stock alert: Small eggs are running low (10 remaining)',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->info("Test notification created for: {$user->name} ({$user->email})");
        $this->info("Unread notification count: " . $user->unreadNotifications()->count());
        
        return 0;
    }
}
