<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Notifications\PickupReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendPickupReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:remind 
                            {--hours=24 : Send reminders for pickups due within this many hours}
                            {--dry-run : Show what would be sent without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send pickup reminder notifications to customers with upcoming reservation pickups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $isDryRun = $this->option('dry-run');
        
        $this->info("Checking for reservations with pickup due within {$hours} hours...");

        // Find pending reservations with pickup deadline within the specified hours
        // that haven't already been reminded
        $reservations = Reservation::where('status', 'pending')
            ->whereBetween('pickup_deadline', [
                Carbon::now(),
                Carbon::now()->addHours($hours),
            ])
            ->whereNull('reminder_sent_at') // Don't send duplicate reminders
            ->whereNotNull('customer_id') // Only for registered customers
            ->with(['customer', 'shop', 'items.eggCategory'])
            ->get();

        if ($reservations->isEmpty()) {
            $this->info('No reservations need reminders.');
            return Command::SUCCESS;
        }

        $this->info("Found {$reservations->count()} reservation(s) needing reminders.");

        if ($isDryRun) {
            $this->table(
                ['ID', 'Customer', 'Email', 'Shop', 'Pickup Deadline', 'Hours Left'],
                $reservations->map(fn ($r) => [
                    $r->id,
                    $r->customer->name,
                    $r->customer->email,
                    $r->shop->name,
                    $r->pickup_deadline->format('Y-m-d H:i'),
                    round($r->pickup_deadline->diffInHours(Carbon::now()), 1),
                ])
            );
            $this->warn('Dry run - no notifications sent.');
            return Command::SUCCESS;
        }

        $sentCount = 0;
        $errorCount = 0;

        foreach ($reservations as $reservation) {
            try {
                // Send notification to customer
                $reservation->customer->notify(new PickupReminder($reservation));

                // Mark as reminded to avoid duplicates
                $reservation->update(['reminder_sent_at' => now()]);

                $sentCount++;
                $this->line("✓ Sent reminder for reservation #{$reservation->id} to {$reservation->customer->email}");
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("✗ Failed to send reminder for reservation #{$reservation->id}: {$e->getMessage()}");
                Log::error("Failed to send pickup reminder", [
                    'reservation_id' => $reservation->id,
                    'customer_id' => $reservation->customer_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Summary: {$sentCount} sent, {$errorCount} failed.");

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
