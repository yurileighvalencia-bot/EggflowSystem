<?php

namespace App\Services\Reports;

use App\Models\Inventory;
use App\Models\Batch;
use App\Models\EggCategory;
use App\Models\Shop;
use App\Models\DeliveryItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryReportService
{
    /**
     * Get current inventory snapshot.
     */
    public function getCurrentSnapshot(?int $shopId = null): array
    {
        $query = Inventory::with(['shop', 'batch.eggCategory', 'eggCategory']);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $inventory = $query->get();

        // Group by shop
        $byShop = $inventory->groupBy('shop_id')->map(function ($shopInventory) {
            $shop = $shopInventory->first()->shop;
            
            // Group by category within shop
            $byCategory = $shopInventory->groupBy('egg_category_id')->map(function ($catInventory) {
                $category = $catInventory->first()->eggCategory;
                
                return [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'available_stock' => $catInventory->sum('available_stock'),
                    'reserved_stock' => $catInventory->sum('reserved_stock'),
                    'total_stock' => $catInventory->sum('available_stock') + $catInventory->sum('reserved_stock'),
                    'batch_count' => $catInventory->count(),
                    'batches' => $catInventory->map(fn ($inv) => [
                        'batch_id' => $inv->batch_id,
                        'batch_number' => $inv->batch->batch_number ?? 'N/A',
                        'available' => $inv->available_stock,
                        'reserved' => $inv->reserved_stock,
                        'expiry_date' => $inv->batch->expiry_date?->toDateString(),
                        'days_until_expiry' => $inv->batch->expiry_date 
                            ? now()->diffInDays($inv->batch->expiry_date, false) 
                            : null,
                    ])->values()->toArray(),
                ];
            })->values();

            return [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'total_available' => $shopInventory->sum('available_stock'),
                'total_reserved' => $shopInventory->sum('reserved_stock'),
                'total_stock' => $shopInventory->sum('available_stock') + $shopInventory->sum('reserved_stock'),
                'categories' => $byCategory->toArray(),
            ];
        })->values();

        // Calculate totals
        $totalAvailable = $inventory->sum('available_stock');
        $totalReserved = $inventory->sum('reserved_stock');

        return [
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_available_stock' => $totalAvailable,
                'total_reserved_stock' => $totalReserved,
                'total_stock' => $totalAvailable + $totalReserved,
                'shops_count' => $byShop->count(),
                'unique_batches' => $inventory->pluck('batch_id')->unique()->count(),
            ],
            'by_shop' => $byShop->toArray(),
        ];
    }

    /**
     * Get low stock report.
     */
    public function getLowStockReport(?int $shopId = null): array
    {
        $query = Inventory::with(['shop', 'batch.eggCategory', 'eggCategory'])
            ->where('available_stock', '>', 0);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $inventory = $query->get();

        // Filter to low stock items
        $lowStock = $inventory->filter(fn ($inv) => $inv->isBelowThreshold());

        $items = $lowStock->map(fn ($inv) => [
            'shop_id' => $inv->shop_id,
            'shop_name' => $inv->shop->name,
            'category_id' => $inv->egg_category_id,
            'category_name' => $inv->eggCategory->name,
            'batch_id' => $inv->batch_id,
            'batch_number' => $inv->batch->batch_number ?? 'N/A',
            'available_stock' => $inv->available_stock,
            'reorder_level' => $inv->reorder_level ?? 100,
            'shortage' => max(0, ($inv->reorder_level ?? 100) - $inv->available_stock),
        ])->sortByDesc('shortage')->values();

        return [
            'generated_at' => now()->toISOString(),
            'low_stock_count' => $lowStock->count(),
            'items' => $items->toArray(),
        ];
    }

    /**
     * Get expiring inventory report.
     */
    public function getExpiringReport(int $withinDays = 7, ?int $shopId = null): array
    {
        $query = Inventory::with(['shop', 'batch.eggCategory', 'eggCategory'])
            ->whereHas('batch', function ($q) use ($withinDays) {
                $q->whereBetween('expiry_date', [now(), now()->addDays($withinDays)]);
            })
            ->where(function ($q) {
                $q->where('available_stock', '>', 0)
                  ->orWhere('reserved_stock', '>', 0);
            });

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $inventory = $query->get();

        // Group by days until expiry
        $byDays = $inventory->groupBy(function ($inv) {
            return $inv->batch->expiry_date->diffInDays(now());
        })->sortKeys();

        $items = $inventory->map(fn ($inv) => [
            'shop_id' => $inv->shop_id,
            'shop_name' => $inv->shop->name,
            'category_id' => $inv->egg_category_id,
            'category_name' => $inv->eggCategory->name,
            'batch_id' => $inv->batch_id,
            'batch_number' => $inv->batch->batch_number ?? 'N/A',
            'available_stock' => $inv->available_stock,
            'reserved_stock' => $inv->reserved_stock,
            'expiry_date' => $inv->batch->expiry_date->toDateString(),
            'days_until_expiry' => $inv->batch->expiry_date->diffInDays(now()),
        ])->sortBy('days_until_expiry')->values();

        return [
            'generated_at' => now()->toISOString(),
            'within_days' => $withinDays,
            'expiring_batches_count' => $inventory->pluck('batch_id')->unique()->count(),
            'total_expiring_stock' => $inventory->sum('available_stock') + $inventory->sum('reserved_stock'),
            'by_days_remaining' => $byDays->map(fn ($group, $days) => [
                'days' => $days,
                'batches' => $group->count(),
                'stock' => $group->sum('available_stock') + $group->sum('reserved_stock'),
            ])->values()->toArray(),
            'items' => $items->toArray(),
        ];
    }

    /**
     * Get inventory movement report.
     */
    public function getMovementReport(Carbon $startDate, Carbon $endDate, ?int $shopId = null): array
    {
        // Inbound (deliveries received)
        $inboundQuery = DeliveryItem::whereHas('delivery', function ($q) use ($startDate, $endDate, $shopId) {
            $q->whereBetween('received_at', [$startDate, $endDate])
              ->where('status', 'received');
            
            if ($shopId) {
                $q->where('shop_id', $shopId);
            }
        })
        ->selectRaw('egg_category_id, SUM(quantity_received) as total')
        ->groupBy('egg_category_id')
        ->get()
        ->keyBy('egg_category_id');

        // Outbound (sales)
        $outboundQuery = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('sales.status', 'completed');
        
        if ($shopId) {
            $outboundQuery->where('sales.shop_id', $shopId);
        }

        $outbound = $outboundQuery
            ->selectRaw('sale_items.egg_category_id, SUM(sale_items.quantity) as total')
            ->groupBy('sale_items.egg_category_id')
            ->get()
            ->keyBy('egg_category_id');

        // Wastage
        $wastageQuery = DB::table('wastage_logs')
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        if ($shopId) {
            $wastageQuery->where('shop_id', $shopId);
        }

        $wastage = $wastageQuery
            ->selectRaw('egg_category_id, SUM(quantity) as total')
            ->groupBy('egg_category_id')
            ->get()
            ->keyBy('egg_category_id');

        // Combine by category
        $categories = EggCategory::where('is_active', true)->get();
        
        $movements = $categories->map(function ($cat) use ($inboundQuery, $outbound, $wastage) {
            $inQty = (int) ($inboundQuery[$cat->id]->total ?? 0);
            $outQty = (int) ($outbound[$cat->id]->total ?? 0);
            $wasteQty = (int) ($wastage[$cat->id]->total ?? 0);

            return [
                'category_id' => $cat->id,
                'category_name' => $cat->name,
                'inbound' => $inQty,
                'outbound' => $outQty,
                'wastage' => $wasteQty,
                'net_change' => $inQty - $outQty - $wasteQty,
            ];
        })->filter(fn ($m) => $m['inbound'] > 0 || $m['outbound'] > 0 || $m['wastage'] > 0)
          ->values();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_inbound' => $movements->sum('inbound'),
                'total_outbound' => $movements->sum('outbound'),
                'total_wastage' => $movements->sum('wastage'),
                'net_change' => $movements->sum('net_change'),
            ],
            'by_category' => $movements->toArray(),
        ];
    }

    /**
     * Get stock valuation report.
     */
    public function getValuationReport(?int $shopId = null): array
    {
        $query = Inventory::with(['shop', 'batch.eggCategory', 'eggCategory']);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $inventory = $query->get();

        $items = $inventory->map(function ($inv) {
            $unitPrice = $inv->unit_price ?? $inv->eggCategory->unit_price ?? 0;
            $totalStock = $inv->available_stock + $inv->reserved_stock;
            
            return [
                'shop_id' => $inv->shop_id,
                'shop_name' => $inv->shop->name,
                'category_id' => $inv->egg_category_id,
                'category_name' => $inv->eggCategory->name,
                'batch_id' => $inv->batch_id,
                'batch_number' => $inv->batch->batch_number ?? 'N/A',
                'stock' => $totalStock,
                'unit_price' => (float) $unitPrice,
                'value' => (float) ($totalStock * $unitPrice),
            ];
        })->filter(fn ($item) => $item['stock'] > 0);

        // Summary by shop
        $byShop = $items->groupBy('shop_id')->map(fn ($group) => [
            'shop_name' => $group->first()['shop_name'],
            'total_stock' => $group->sum('stock'),
            'total_value' => $group->sum('value'),
        ])->values();

        // Summary by category
        $byCategory = $items->groupBy('category_id')->map(fn ($group) => [
            'category_name' => $group->first()['category_name'],
            'total_stock' => $group->sum('stock'),
            'total_value' => $group->sum('value'),
        ])->values();

        return [
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_stock' => $items->sum('stock'),
                'total_value' => $items->sum('value'),
            ],
            'by_shop' => $byShop->toArray(),
            'by_category' => $byCategory->toArray(),
        ];
    }
}
