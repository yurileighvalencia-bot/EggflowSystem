<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
|
| These commands run automatically based on the schedule defined below.
| Run `php artisan schedule:run` every minute via cron for these to work.
|
*/

// Expire reservations that have passed their pickup deadline
// Runs every 15 minutes to catch expired reservations promptly
Schedule::command('reservations:expire')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reservations-expire.log'));

// Send pickup reminders for reservations due within 24 hours
// Runs every hour to ensure customers get timely reminders
Schedule::command('reservations:remind --hours=24')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reservations-remind.log'));

// Check for expired batches and log wastage
// Runs daily at 1 AM to process overnight expirations
Schedule::command('batches:check-expiry --warn-days=3')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/batch-expiry.log'));
