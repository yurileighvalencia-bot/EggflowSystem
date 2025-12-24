<?php

namespace App\Console\Commands;

use App\Events\LowStockDetected;
use App\Models\EggCategory;
use App\Models\Inventory;
use App\Models\Shop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckLowStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:check-low-stock 
                            {--threshold=50 : Minimum stock level before triggering alert}
                            {--dry-run : Show alerts without sending notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check inventory levels across all shops and trigger low stock alerts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $threshold = (int) $this->option('threshold');
        $isDryRun = $this->option('dry-run');

        $this->info("Checking inventory levels (threshold: {$threshold})...");

        // Get stock levels grouped by shop and category
        $lowStockItems = Inventory::select(
                'shop_id',
                'egg_category_id',
                DB::raw('SUM(available_stock) as total_available')
            )
            ->whereHas('batch', fn ($q) => $q->where('status', 'active'))
            ->groupBy('shop_id', 'egg_category_id')
            ->having('total_available', '<', $threshold)
            ->having('total_available', '>', 0) // Don't alert for zero stock
            ->with(['shop', 'eggCategory'])
            ->get();

        if ($lowStockItems->isEmpty()) {
            $this->info('No low stock alerts to send.');
            return Command::SUCCESS;
        }

        $this->info("Found {$lowStockItems->count()} low stock item(s).");

        if ($isDryRun) {
            $this->table(
                ['Shop', 'Category', 'Current Stock', 'Threshold'],
                $lowStockItems->map(fn ($item) => [
                    Shop::find($item->shop_id)?->name ?? 'Unknown',
                    EggCategory::find($item->egg_category_id)?->name ?? 'Unknown',
                    $item->total_available,
                    $threshold,
                ])
            );
            $this->warn('Dry run - no alerts sent.');
            return Command::SUCCESS;
        }

        $alertsSent = 0;

        foreach ($lowStockItems as $item) {
            $category = EggCategory::find($item->egg_category_id);
            
            if (!$category) {
                continue;
            }

            try {
                event(new LowStockDetected(
                    $item->shop_id,
                    $category,
                    (int) $item->total_available,
                    $threshold
                ));

                $shopName = Shop::find($item->shop_id)?->name ?? 'Unknown';
                $this->line("✓ Alert sent: {$shopName} - {$category->name} ({$item->total_available} remaining)");
                $alertsSent++;
            } catch (\Exception $e) {
                $this->error("✗ Failed to send alert for shop {$item->shop_id}: " . $e->getMessage());
                Log::error("Low stock alert failed", [
                    'shop_id' => $item->shop_id,
                    'category_id' => $item->egg_category_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Alerts sent: {$alertsSent}");
        
        return Command::SUCCESS;
    }
}
