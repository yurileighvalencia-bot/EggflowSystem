<?php

namespace App\Listeners;

use App\Events\SaleCompleted;
use App\Services\InventoryService;
use App\Events\LowStockDetected;
use App\Models\EggCategory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CheckStockAfterSale implements ShouldQueue
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(SaleCompleted $event): void
    {
        $sale = $event->sale;
        $shopId = $sale->shop_id;

        // Get unique categories from sale items
        $categoryIds = $sale->items->pluck('egg_category_id')->unique();

        foreach ($categoryIds as $categoryId) {
            $summary = $this->inventoryService->getStockSummary($shopId, $categoryId);

            if ($summary['is_low']) {
                $category = EggCategory::find($categoryId);
                
                event(new LowStockDetected(
                    $shopId,
                    $category,
                    $summary['available'],
                    $summary['threshold']
                ));

                Log::info("Low stock detected after sale for shop {$shopId}, category {$category->name}");
            }
        }
    }
}
