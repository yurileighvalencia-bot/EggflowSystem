<?php

namespace App\Console\Commands;

use App\Events\ReservationExpired;
use App\Models\Reservation;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:expire 
                            {--dry-run : Show what would be expired without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire reservations that have passed their pickup deadline and release reserved stock';

    /**
     * Execute the console command.
     */
    public function handle(InventoryService $inventoryService): int
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('Checking for expired reservations...');

        $expiredReservations = Reservation::where('status', 'pending')
            ->where('pickup_deadline', '<', Carbon::now())
            ->with(['items.eggCategory', 'customer', 'shop'])
            ->get();

        if ($expiredReservations->isEmpty()) {
            $this->info('No expired reservations found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$expiredReservations->count()} reservation(s) to expire.");

        if ($isDryRun) {
            $this->table(
                ['ID', 'Customer', 'Shop', 'Pickup Deadline', 'Total'],
                $expiredReservations->map(fn ($r) => [
                    $r->id,
                    $r->customer?->name ?? 'Walk-in',
                    $r->shop->name,
                    $r->pickup_deadline->format('Y-m-d H:i'),
                    number_format($r->total_amount, 2),
                ])
            );
            $this->warn('Dry run - no changes made.');
            return Command::SUCCESS;
        }

        $expiredCount = 0;
        $errorCount = 0;

        foreach ($expiredReservations as $reservation) {
            try {
                DB::transaction(function () use ($reservation, $inventoryService, &$expiredCount) {
                    // Release reserved stock for each item
                    foreach ($reservation->items as $item) {
                        $inventoryService->releaseReservedStock(
                            $reservation->shop_id,
                            $item->egg_category_id,
                            $item->quantity
                        );
                    }

                    // Update reservation status
                    $reservation->update([
                        'status' => Reservation::STATUS_EXPIRED,
                        'expired_at' => now(),
                    ]);

                    // Fire event for notifications
                    event(new ReservationExpired($reservation));

                    $expiredCount++;
                });

                $this->line("✓ Expired reservation #{$reservation->id}");
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("✗ Failed to expire reservation #{$reservation->id}: {$e->getMessage()}");
                Log::error("Failed to expire reservation", [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Summary: {$expiredCount} expired, {$errorCount} failed.");

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
