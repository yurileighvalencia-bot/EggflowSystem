<?php

namespace App\Services\Reports;

use App\Models\DailyCollection;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Batch;
use App\Models\WastageLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CollectionSalesComparisonService
{
    /**
     * Compare collection vs sales for a date range.
     */
    public function getComparison(Carbon $startDate, Carbon $endDate, ?int $farmId = null, ?int $shopId = null): array
    {
        // Collections data
        $collectionsQuery = DailyCollection::with('eggCategory')
            ->whereBetween('collection_date', [$startDate, $endDate]);
        
        if ($farmId) {
            $collectionsQuery->where('farm_id', $farmId);
        }

        $collections = $collectionsQuery->get();
        $totalCollected = $collections->sum('quantity_collected');
        $totalDamaged = $collections->sum('damaged_quantity');
        $netCollection = $totalCollected - $totalDamaged;

        // Sales data
        $salesQuery = SaleItem::whereHas('sale', function ($q) use ($startDate, $endDate, $shopId) {
            $q->whereBetween('created_at', [$startDate, $endDate])
              ->where('status', 'completed');
            
            if ($shopId) {
                $q->where('shop_id', $shopId);
            }
        });

        $salesItems = $salesQuery->with('eggCategory')->get();
        $totalSold = $salesItems->sum('quantity');
        $totalRevenue = $salesItems->sum('line_total');

        // Wastage during period
        $wastageQuery = WastageLog::whereBetween('created_at', [$startDate, $endDate]);
        if ($shopId) {
            $wastageQuery->where('shop_id', $shopId);
        }
        $totalWastage = $wastageQuery->sum('quantity');

        // By category comparison
        $categoryComparison = $this->getCategoryComparison($collections, $salesItems);

        // Daily comparison
        $dailyComparison = $this->getDailyComparison($startDate, $endDate, $farmId, $shopId);

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'collection' => [
                'total_collected' => $totalCollected,
                'damaged' => $totalDamaged,
                'net_usable' => $netCollection,
                'damage_rate' => $totalCollected > 0 
                    ? round(($totalDamaged / $totalCollected) * 100, 2) 
                    : 0,
            ],
            'sales' => [
                'total_sold' => $totalSold,
                'total_revenue' => (float) $totalRevenue,
                'average_price' => $totalSold > 0 
                    ? round($totalRevenue / $totalSold, 2) 
                    : 0,
            ],
            'wastage' => [
                'total' => (int) $totalWastage,
                'rate' => $netCollection > 0 
                    ? round(($totalWastage / $netCollection) * 100, 2) 
                    : 0,
            ],
            'efficiency' => [
                'collection_to_sale_rate' => $netCollection > 0 
                    ? round(($totalSold / $netCollection) * 100, 2) 
                    : 0,
                'overall_efficiency' => $totalCollected > 0 
                    ? round(($totalSold / $totalCollected) * 100, 2) 
                    : 0,
                'stock_remaining' => max(0, $netCollection - $totalSold - $totalWastage),
            ],
            'by_category' => $categoryComparison,
            'daily_trend' => $dailyComparison,
        ];
    }

    /**
     * Get week-over-week comparison.
     */
    public function getWeeklyComparison(?int $shopId = null): array
    {
        $thisWeekStart = now()->startOfWeek();
        $thisWeekEnd = now()->endOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();

        $thisWeek = $this->getComparison($thisWeekStart, $thisWeekEnd, null, $shopId);
        $lastWeek = $this->getComparison($lastWeekStart, $lastWeekEnd, null, $shopId);

        return [
            'this_week' => $thisWeek,
            'last_week' => $lastWeek,
            'changes' => [
                'collection_change' => $this->calculateChange(
                    $lastWeek['collection']['net_usable'],
                    $thisWeek['collection']['net_usable']
                ),
                'sales_change' => $this->calculateChange(
                    $lastWeek['sales']['total_sold'],
                    $thisWeek['sales']['total_sold']
                ),
                'revenue_change' => $this->calculateChange(
                    $lastWeek['sales']['total_revenue'],
                    $thisWeek['sales']['total_revenue']
                ),
                'wastage_change' => $this->calculateChange(
                    $lastWeek['wastage']['total'],
                    $thisWeek['wastage']['total']
                ),
            ],
        ];
    }

    /**
     * Get monthly comparison for a year.
     */
    public function getMonthlyTrend(int $year, ?int $shopId = null): array
    {
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            // Skip future months
            if ($startDate->isFuture()) {
                continue;
            }

            $comparison = $this->getComparison($startDate, $endDate, null, $shopId);

            $months[] = [
                'month' => $month,
                'month_name' => $startDate->format('F'),
                'collected' => $comparison['collection']['net_usable'],
                'sold' => $comparison['sales']['total_sold'],
                'revenue' => $comparison['sales']['total_revenue'],
                'wastage' => $comparison['wastage']['total'],
                'efficiency' => $comparison['efficiency']['overall_efficiency'],
            ];
        }

        return [
            'year' => $year,
            'months' => $months,
            'yearly_totals' => [
                'collected' => array_sum(array_column($months, 'collected')),
                'sold' => array_sum(array_column($months, 'sold')),
                'revenue' => array_sum(array_column($months, 'revenue')),
                'wastage' => array_sum(array_column($months, 'wastage')),
            ],
        ];
    }

    /**
     * Get category-level comparison.
     */
    protected function getCategoryComparison($collections, $salesItems): array
    {
        $collectionByCategory = $collections->groupBy('egg_category_id');
        $salesByCategory = $salesItems->groupBy('egg_category_id');

        $categories = $collectionByCategory->keys()
            ->merge($salesByCategory->keys())
            ->unique();

        return $categories->map(function ($categoryId) use ($collectionByCategory, $salesByCategory) {
            $catCollections = $collectionByCategory->get($categoryId, collect());
            $catSales = $salesByCategory->get($categoryId, collect());

            $collected = $catCollections->sum('quantity_collected') - $catCollections->sum('damaged_quantity');
            $sold = $catSales->sum('quantity');

            return [
                'category_id' => $categoryId,
                'category_name' => $catCollections->first()?->eggCategory->name 
                    ?? $catSales->first()?->eggCategory->name 
                    ?? 'Unknown',
                'collected' => $collected,
                'sold' => $sold,
                'revenue' => (float) $catSales->sum('line_total'),
                'efficiency' => $collected > 0 ? round(($sold / $collected) * 100, 2) : 0,
            ];
        })->values()->toArray();
    }

    /**
     * Get daily collection vs sales trend.
     */
    protected function getDailyComparison(Carbon $startDate, Carbon $endDate, ?int $farmId, ?int $shopId): array
    {
        $dailyData = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $dateStr = $current->toDateString();

            // Collections for this day
            $collectQuery = DailyCollection::whereDate('collection_date', $current);
            if ($farmId) {
                $collectQuery->where('farm_id', $farmId);
            }
            $dayCollection = $collectQuery->get();
            $collected = $dayCollection->sum('quantity_collected') - $dayCollection->sum('damaged_quantity');

            // Sales for this day
            $salesQuery = Sale::whereDate('created_at', $current)
                ->where('status', 'completed');
            if ($shopId) {
                $salesQuery->where('shop_id', $shopId);
            }
            $daySales = $salesQuery->with('items')->get();
            $sold = $daySales->sum(fn ($sale) => $sale->items->sum('quantity'));

            $dailyData[] = [
                'date' => $dateStr,
                'collected' => $collected,
                'sold' => $sold,
                'difference' => $collected - $sold,
            ];

            $current->addDay();
        }

        return $dailyData;
    }

    /**
     * Calculate percentage change between two values.
     */
    protected function calculateChange($previous, $current): array
    {
        $difference = $current - $previous;
        $percentage = $previous > 0 
            ? round(($difference / $previous) * 100, 2) 
            : ($current > 0 ? 100 : 0);

        return [
            'previous' => $previous,
            'current' => $current,
            'difference' => $difference,
            'percentage' => $percentage,
            'direction' => $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'stable'),
        ];
    }
}
