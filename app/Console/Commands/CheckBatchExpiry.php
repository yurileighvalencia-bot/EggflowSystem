<?php

namespace App\Console\Commands;

use App\Events\BatchExpired;
use App\Models\Batch;
use App\Models\Inventory;
use App\Models\WastageLog;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckBatchExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batches:check-expiry 
                            {--dry-run : Show what would be marked expired without making changes}
                            {--warn-days=3 : Days before expiry to send warnings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired batches and those expiring soon, log wastage accordingly';

    /**
     * Execute the console command.
     */
    public function handle(InventoryService $inventoryService): int
    {
        $isDryRun = $this->option('dry-run');
        $warnDays = (int) $this->option('warn-days');
        
        $this->info('Checking for batch expiry...');

        // Find expired batches that still have stock
        $this->processExpiredBatches($inventoryService, $isDryRun);
        
        $this->newLine();
        
        // Find batches expiring soon for warning
        $this->warnExpiringBatches($warnDays);

        return Command::SUCCESS;
    }

    /**
     * Process batches that have already expired.
     */
    protected function processExpiredBatches(InventoryService $inventoryService, bool $isDryRun): void
    {
        $this->info('Checking for expired batches with remaining stock...');

        // Find inventory entries for expired batches
        $expiredInventory = Inventory::whereHas('batch', function ($query) {
                $query->where('expires_at', '<', Carbon::today());
            })
            ->where(function ($query) {
                $query->where('available_stock', '>', 0)
                    ->orWhere('reserved_stock', '>', 0);
            })
            ->with(['batch.eggCategory', 'shop'])
            ->get();

        if ($expiredInventory->isEmpty()) {
            $this->info('No expired batches with remaining stock found.');
            return;
        }

        $this->info("Found {$expiredInventory->count()} inventory record(s) with expired batches.");

        if ($isDryRun) {
            $this->table(
                ['Batch ID', 'Category', 'Shop', 'Available', 'Reserved', 'Expiry Date'],
                $expiredInventory->map(fn ($inv) => [
                    $inv->batch->batch_code,
                    $inv->batch->eggCategory->name,
                    $inv->shop->name,
                    $inv->available_stock,
                    $inv->reserved_stock,
                    $inv->batch->expires_at->format('Y-m-d'),
                ])
            );
            $this->warn('Dry run - no changes made.');
            return;
        }

        $processedCount = 0;
        $errorCount = 0;

        foreach ($expiredInventory as $inventory) {
            try {
                DB::transaction(function () use ($inventory, $inventoryService, &$processedCount) {
                    $totalWaste = $inventory->available_stock + $inventory->reserved_stock;
                    
                    if ($totalWaste > 0) {
                        // Record wastage for the expired stock
                        $inventoryService->recordWastage(
                            $inventory->shop_id,
                            $inventory->batch->egg_category_id,
                            $totalWaste,
                            WastageLog::SOURCE_BATCH_EXPIRED,
                            'Batch expired - automatic wastage',
                            null, // System action - no user
                            $inventory->batch_id
                        );

                        // Zero out the inventory
                        $inventory->update([
                            'available_stock' => 0,
                            'reserved_stock' => 0,
                        ]);

                        // Fire expiry event
                        event(new BatchExpired($inventory->batch, $totalWaste));
                    }

                    $processedCount++;
                });

                $wastedQuantity = $inventory->available_stock + $inventory->reserved_stock;
                $this->line("✓ Processed expired batch #{$inventory->batch->batch_code} ({$wastedQuantity} eggs wasted)");
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("✗ Failed to process batch #{$inventory->batch->batch_code}: " . $e->getMessage());
                Log::error("Failed to process expired batch", [
                    'batch_id' => $inventory->batch_id,
                    'inventory_id' => $inventory->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Processed: {$processedCount}, Failed: {$errorCount}");
    }

    /**
     * Warn about batches expiring soon.
     */
    protected function warnExpiringBatches(int $warnDays): void
    {
        $this->info("Checking for batches expiring within {$warnDays} days...");

        $expiringInventory = Inventory::whereHas('batch', function ($query) use ($warnDays) {
                $query->whereBetween('expires_at', [
                    Carbon::today(),
                    Carbon::today()->addDays($warnDays),
                ]);
            })
            ->where(function ($query) {
                $query->where('available_stock', '>', 0)
                    ->orWhere('reserved_stock', '>', 0);
            })
            ->with(['batch.eggCategory', 'shop'])
            ->get();

        if ($expiringInventory->isEmpty()) {
            $this->info('No batches expiring soon.');
            return;
        }

        $this->warn("⚠ {$expiringInventory->count()} inventory record(s) expiring within {$warnDays} days:");

        $this->table(
            ['Batch ID', 'Category', 'Shop', 'Available', 'Reserved', 'Expiry Date', 'Days Left'],
            $expiringInventory->map(fn ($inv) => [
                $inv->batch->batch_code,
                $inv->batch->eggCategory->name,
                $inv->shop->name,
                $inv->available_stock,
                $inv->reserved_stock,
                $inv->batch->expires_at->format('Y-m-d'),
                $inv->batch->expires_at->diffInDays(Carbon::today()),
            ])
        );

        // Log for monitoring systems
        Log::warning('Batches expiring soon', [
            'count' => $expiringInventory->count(),
            'batches' => $expiringInventory->map(fn ($inv) => [
                'batch_id' => $inv->batch_id,
                'shop_id' => $inv->shop_id,
                'available' => $inv->available_stock,
                'expiry' => $inv->batch->expires_at->toDateString(),
            ])->toArray(),
        ]);
    }
}
