<?php

namespace App\Services\Reports;

use App\Models\WastageLog;
use App\Models\EggCategory;
use App\Models\Shop;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WastageReportService
{
    /**
     * Get wastage summary for a date range.
     */
    public function getSummary(Carbon $startDate, Carbon $endDate, ?int $shopId = null): array
    {
        $query = WastageLog::with(['shop', 'eggCategory', 'batch', 'reportedBy'])
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()]);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $wastage = $query->get();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_quantity' => $wastage->sum('quantity'),
                'total_records' => $wastage->count(),
                'estimated_value' => $this->estimateValue($wastage),
            ],
            'by_source' => $this->groupBySource($wastage),
            'by_category' => $this->groupByCategory($wastage),
            'by_shop' => $shopId ? null : $this->groupByShop($wastage),
            'by_reason' => $this->groupByReason($wastage),
            'daily_trend' => $this->getDailyTrend($startDate, $endDate, $shopId),
        ];
    }

    /**
     * Get wastage trends over time.
     */
    public function getTrends(int $months = 6, ?int $shopId = null): array
    {
        $trends = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $query = WastageLog::whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            
            if ($shopId) {
                $query->where('shop_id', $shopId);
            }

            $monthWastage = $query->get();

            $trends[] = [
                'month' => $month->format('Y-m'),
                'month_name' => $month->format('F Y'),
                'total_quantity' => $monthWastage->sum('quantity'),
                'record_count' => $monthWastage->count(),
                'estimated_value' => $this->estimateValue($monthWastage),
            ];
        }

        // Calculate average and identify concerning trends
        $avgQuantity = collect($trends)->avg('total_quantity');
        $lastMonth = end($trends);
        
        return [
            'trends' => $trends,
            'analysis' => [
                'average_monthly_wastage' => round($avgQuantity, 2),
                'last_month_quantity' => $lastMonth['total_quantity'],
                'trend_direction' => $this->determineTrend($trends),
                'highest_month' => collect($trends)->sortByDesc('total_quantity')->first(),
                'lowest_month' => collect($trends)->sortBy('total_quantity')->first(),
            ],
        ];
    }

    /**
     * Get wastage by source breakdown.
     */
    public function getBySourceReport(Carbon $startDate, Carbon $endDate, ?int $shopId = null): array
    {
        $query = WastageLog::with(['eggCategory'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $wastage = $query->get();

        $sources = [
            'collection' => 'Collection (Damaged during collection)',
            'delivery' => 'Delivery (Transit damage/discrepancy)',
            'storage' => 'Storage (Expired/spoiled in shop)',
            'handling' => 'Handling (Broken during handling)',
            'other' => 'Other',
        ];

        $breakdown = [];
        foreach ($sources as $source => $description) {
            $sourceWastage = $wastage->where('source', $source);
            
            $breakdown[$source] = [
                'description' => $description,
                'quantity' => $sourceWastage->sum('quantity'),
                'records' => $sourceWastage->count(),
                'percentage' => $wastage->sum('quantity') > 0 
                    ? round(($sourceWastage->sum('quantity') / $wastage->sum('quantity')) * 100, 2) 
                    : 0,
                'by_category' => $sourceWastage->groupBy('egg_category_id')
                    ->map(fn ($group) => [
                        'category' => $group->first()->eggCategory->name ?? 'Unknown',
                        'quantity' => $group->sum('quantity'),
                    ])
                    ->values()
                    ->toArray(),
            ];
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'total_wastage' => $wastage->sum('quantity'),
            'by_source' => $breakdown,
        ];
    }

    /**
     * Get delivery discrepancy analysis.
     */
    public function getDiscrepancyAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $discrepancies = DB::table('delivery_discrepancies')
            ->join('deliveries', 'delivery_discrepancies.delivery_id', '=', 'deliveries.id')
            ->join('shops', 'deliveries.shop_id', '=', 'shops.id')
            ->join('farms', 'deliveries.farm_id', '=', 'farms.id')
            ->whereBetween('delivery_discrepancies.created_at', [$startDate, $endDate])
            ->select([
                'delivery_discrepancies.*',
                'shops.name as shop_name',
                'farms.name as farm_name',
                'deliveries.delivery_number',
            ])
            ->get();

        // Group by type
        $byType = $discrepancies->groupBy('discrepancy_type')->map(fn ($group) => [
            'count' => $group->count(),
            'total_shortage' => $group->sum(fn ($d) => $d->quantity_expected - $d->quantity_received),
        ]);

        // Group by route (farm -> shop)
        $byRoute = $discrepancies->groupBy(fn ($d) => "{$d->farm_name} → {$d->shop_name}")
            ->map(fn ($group) => [
                'count' => $group->count(),
                'total_shortage' => $group->sum(fn ($d) => $d->quantity_expected - $d->quantity_received),
            ])
            ->sortByDesc('count');

        // Resolution status
        $resolved = $discrepancies->whereNotNull('resolution')->count();
        $pending = $discrepancies->whereNull('resolution')->count();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_discrepancies' => $discrepancies->count(),
                'total_shortage' => $discrepancies->sum(fn ($d) => max(0, $d->quantity_expected - $d->quantity_received)),
                'resolved' => $resolved,
                'pending' => $pending,
                'resolution_rate' => $discrepancies->count() > 0 
                    ? round(($resolved / $discrepancies->count()) * 100, 2) 
                    : 100,
            ],
            'by_type' => $byType->toArray(),
            'by_route' => $byRoute->take(10)->toArray(),
        ];
    }

    /**
     * Compare collection vs actual usable eggs.
     */
    public function getCollectionEfficiencyReport(Carbon $startDate, Carbon $endDate, ?int $farmId = null): array
    {
        $query = DB::table('daily_collections')
            ->whereBetween('collection_date', [$startDate, $endDate]);

        if ($farmId) {
            $query->where('farm_id', $farmId);
        }

        $collections = $query
            ->selectRaw('SUM(quantity_collected) as total_collected')
            ->selectRaw('SUM(damaged_quantity) as total_damaged')
            ->first();

        $totalCollected = (int) ($collections->total_collected ?? 0);
        $totalDamaged = (int) ($collections->total_damaged ?? 0);
        $usable = $totalCollected - $totalDamaged;

        // Get wastage during same period from delivery
        $deliveryWastageQuery = WastageLog::whereBetween('created_at', [$startDate, $endDate])
            ->where('source', 'delivery');
        
        $deliveryWastage = $deliveryWastageQuery->sum('quantity');

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'collection' => [
                'total_collected' => $totalCollected,
                'damaged_at_collection' => $totalDamaged,
                'usable_from_collection' => $usable,
                'collection_efficiency' => $totalCollected > 0 
                    ? round(($usable / $totalCollected) * 100, 2) 
                    : 100,
            ],
            'transit' => [
                'lost_in_transit' => (int) $deliveryWastage,
            ],
            'overall' => [
                'final_usable' => $usable - $deliveryWastage,
                'total_loss' => $totalDamaged + $deliveryWastage,
                'overall_efficiency' => $totalCollected > 0 
                    ? round((($usable - $deliveryWastage) / $totalCollected) * 100, 2) 
                    : 100,
            ],
        ];
    }

    /**
     * Estimate monetary value of wastage.
     */
    protected function estimateValue(Collection $wastage): float
    {
        return $wastage->sum(function ($log) {
            $unitPrice = $log->eggCategory->unit_price ?? 0;
            return $log->quantity * $unitPrice;
        });
    }

    /**
     * Group wastage by source.
     */
    protected function groupBySource(Collection $wastage): array
    {
        return $wastage->groupBy('source')
            ->map(fn ($group) => [
                'quantity' => $group->sum('quantity'),
                'count' => $group->count(),
            ])
            ->toArray();
    }

    /**
     * Group wastage by category.
     */
    protected function groupByCategory(Collection $wastage): array
    {
        return $wastage->groupBy('egg_category_id')
            ->map(fn ($group) => [
                'category_name' => $group->first()->eggCategory->name ?? 'Unknown',
                'quantity' => $group->sum('quantity'),
                'count' => $group->count(),
            ])
            ->sortByDesc('quantity')
            ->values()
            ->toArray();
    }

    /**
     * Group wastage by shop.
     */
    protected function groupByShop(Collection $wastage): array
    {
        return $wastage->groupBy('shop_id')
            ->map(fn ($group) => [
                'shop_name' => $group->first()->shop->name ?? 'Unknown',
                'quantity' => $group->sum('quantity'),
                'count' => $group->count(),
            ])
            ->sortByDesc('quantity')
            ->values()
            ->toArray();
    }

    /**
     * Group wastage by reason.
     */
    protected function groupByReason(Collection $wastage): array
    {
        return $wastage->groupBy('reason')
            ->map(fn ($group) => [
                'reason' => $group->first()->reason ?? 'No reason specified',
                'quantity' => $group->sum('quantity'),
                'count' => $group->count(),
            ])
            ->sortByDesc('quantity')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * Get daily wastage trend.
     */
    protected function getDailyTrend(Carbon $startDate, Carbon $endDate, ?int $shopId = null): array
    {
        $query = WastageLog::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('SUM(quantity) as total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date');

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'quantity' => (int) $row->total,
            ])
            ->toArray();
    }

    /**
     * Determine trend direction from historical data.
     */
    protected function determineTrend(array $trends): string
    {
        if (count($trends) < 2) {
            return 'stable';
        }

        $recent = array_slice($trends, -3);
        $values = array_column($recent, 'total_quantity');
        
        $firstHalf = array_slice($values, 0, (int) ceil(count($values) / 2));
        $secondHalf = array_slice($values, (int) ceil(count($values) / 2));
        
        $firstAvg = array_sum($firstHalf) / max(count($firstHalf), 1);
        $secondAvg = array_sum($secondHalf) / max(count($secondHalf), 1);
        
        $changePercent = $firstAvg > 0 ? (($secondAvg - $firstAvg) / $firstAvg) * 100 : 0;
        
        if ($changePercent > 10) {
            return 'increasing';
        } elseif ($changePercent < -10) {
            return 'decreasing';
        }
        
        return 'stable';
    }
}
