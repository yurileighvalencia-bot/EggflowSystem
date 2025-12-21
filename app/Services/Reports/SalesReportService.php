<?php

namespace App\Services\Reports;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\EggCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    /**
     * Get daily sales summary.
     */
    public function getDailySummary(Carbon $date, ?int $shopId = null): array
    {
        $query = Sale::whereDate('created_at', $date)
            ->where('status', 'completed');

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $sales = $query->get();

        return [
            'date' => $date->toDateString(),
            'total_transactions' => $sales->count(),
            'total_revenue' => $sales->sum('total'),
            'total_items_sold' => $sales->sum(fn ($sale) => $sale->items->sum('quantity')),
            'average_transaction_value' => $sales->count() > 0 
                ? round($sales->sum('total') / $sales->count(), 2) 
                : 0,
            'payment_breakdown' => $this->getPaymentBreakdown($sales),
            'hourly_sales' => $this->getHourlySales($date, $shopId),
        ];
    }

    /**
     * Get sales report for a date range.
     */
    public function getDateRangeReport(Carbon $startDate, Carbon $endDate, ?int $shopId = null): array
    {
        $query = Sale::whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->where('status', 'completed');

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $sales = $query->with(['items.eggCategory', 'shop'])->get();

        // Daily breakdown
        $dailyData = $sales->groupBy(fn ($sale) => $sale->created_at->toDateString())
            ->map(fn ($daySales) => [
                'transactions' => $daySales->count(),
                'revenue' => $daySales->sum('total'),
                'items_sold' => $daySales->sum(fn ($s) => $s->items->sum('quantity')),
            ]);

        // Fill in missing days with zeros
        $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->addDay());
        $allDays = collect();
        foreach ($period as $day) {
            $dateStr = $day->format('Y-m-d');
            $allDays[$dateStr] = $dailyData[$dateStr] ?? [
                'transactions' => 0,
                'revenue' => 0,
                'items_sold' => 0,
            ];
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->subDay()->toDateString(),
            ],
            'summary' => [
                'total_transactions' => $sales->count(),
                'total_revenue' => $sales->sum('total'),
                'total_items_sold' => $sales->sum(fn ($s) => $s->items->sum('quantity')),
                'average_daily_revenue' => $allDays->count() > 0 
                    ? round($sales->sum('total') / $allDays->count(), 2) 
                    : 0,
                'average_transaction_value' => $sales->count() > 0 
                    ? round($sales->sum('total') / $sales->count(), 2) 
                    : 0,
            ],
            'daily_breakdown' => $allDays,
            'category_breakdown' => $this->getCategoryBreakdown($sales),
            'payment_breakdown' => $this->getPaymentBreakdown($sales),
            'shop_breakdown' => $shopId ? null : $this->getShopBreakdown($sales),
        ];
    }

    /**
     * Get weekly sales report.
     */
    public function getWeeklyReport(?int $shopId = null, ?Carbon $weekStart = null): array
    {
        $weekStart = $weekStart ?? now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $report = $this->getDateRangeReport($weekStart, $weekEnd, $shopId);
        $report['week_number'] = $weekStart->weekOfYear;
        $report['year'] = $weekStart->year;

        // Compare with previous week
        $prevWeekStart = $weekStart->copy()->subWeek();
        $prevWeekEnd = $prevWeekStart->copy()->endOfWeek();
        
        $prevQuery = Sale::whereBetween('created_at', [$prevWeekStart, $prevWeekEnd])
            ->where('status', 'completed');
        
        if ($shopId) {
            $prevQuery->where('shop_id', $shopId);
        }

        $prevRevenue = $prevQuery->sum('total');
        $currentRevenue = $report['summary']['total_revenue'];

        $report['comparison'] = [
            'previous_week_revenue' => $prevRevenue,
            'change_amount' => $currentRevenue - $prevRevenue,
            'change_percentage' => $prevRevenue > 0 
                ? round((($currentRevenue - $prevRevenue) / $prevRevenue) * 100, 2) 
                : 0,
        ];

        return $report;
    }

    /**
     * Get monthly sales report.
     */
    public function getMonthlyReport(int $year, int $month, ?int $shopId = null): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $report = $this->getDateRangeReport($startDate, $endDate, $shopId);
        $report['month'] = $month;
        $report['month_name'] = $startDate->format('F');
        $report['year'] = $year;

        // Weekly breakdown within the month
        $weeklyData = [];
        $currentWeekStart = $startDate->copy()->startOfWeek();
        
        while ($currentWeekStart->lte($endDate)) {
            $weekEnd = $currentWeekStart->copy()->endOfWeek();
            if ($weekEnd->gt($endDate)) {
                $weekEnd = $endDate->copy();
            }
            
            $weekStart = $currentWeekStart->copy();
            if ($weekStart->lt($startDate)) {
                $weekStart = $startDate->copy();
            }

            $weekQuery = Sale::whereBetween('created_at', [$weekStart, $weekEnd])
                ->where('status', 'completed');
            
            if ($shopId) {
                $weekQuery->where('shop_id', $shopId);
            }

            $weeklyData[] = [
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'revenue' => $weekQuery->sum('total'),
                'transactions' => $weekQuery->count(),
            ];

            $currentWeekStart->addWeek();
        }

        $report['weekly_breakdown'] = $weeklyData;

        // Year-over-year comparison
        $prevYearQuery = Sale::whereYear('created_at', $year - 1)
            ->whereMonth('created_at', $month)
            ->where('status', 'completed');
        
        if ($shopId) {
            $prevYearQuery->where('shop_id', $shopId);
        }

        $prevYearRevenue = $prevYearQuery->sum('total');

        $report['yoy_comparison'] = [
            'previous_year_revenue' => $prevYearRevenue,
            'change_amount' => $report['summary']['total_revenue'] - $prevYearRevenue,
            'change_percentage' => $prevYearRevenue > 0 
                ? round((($report['summary']['total_revenue'] - $prevYearRevenue) / $prevYearRevenue) * 100, 2) 
                : 0,
        ];

        return $report;
    }

    /**
     * Get top selling categories.
     */
    public function getTopCategories(Carbon $startDate, Carbon $endDate, ?int $shopId = null, int $limit = 10): Collection
    {
        $query = SaleItem::whereHas('sale', function ($q) use ($startDate, $endDate, $shopId) {
            $q->whereBetween('created_at', [$startDate, $endDate])
              ->where('status', 'completed');
            
            if ($shopId) {
                $q->where('shop_id', $shopId);
            }
        })
        ->select('egg_category_id')
        ->selectRaw('SUM(quantity) as total_quantity')
        ->selectRaw('SUM(line_total) as total_revenue')
        ->groupBy('egg_category_id')
        ->orderByDesc('total_revenue')
        ->limit($limit)
        ->with('eggCategory')
        ->get();

        return $query->map(fn ($item) => [
            'category_id' => $item->egg_category_id,
            'category_name' => $item->eggCategory->name,
            'total_quantity' => (int) $item->total_quantity,
            'total_revenue' => (float) $item->total_revenue,
        ]);
    }

    /**
     * Get payment method breakdown.
     */
    protected function getPaymentBreakdown(Collection $sales): array
    {
        return $sales->groupBy('payment_method')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ])
            ->toArray();
    }

    /**
     * Get category breakdown.
     */
    protected function getCategoryBreakdown(Collection $sales): array
    {
        $items = $sales->flatMap->items;
        
        return $items->groupBy('egg_category_id')
            ->map(fn ($group) => [
                'category_name' => $group->first()->eggCategory->name ?? 'Unknown',
                'quantity_sold' => $group->sum('quantity'),
                'revenue' => $group->sum('line_total'),
            ])
            ->sortByDesc('revenue')
            ->values()
            ->toArray();
    }

    /**
     * Get shop breakdown.
     */
    protected function getShopBreakdown(Collection $sales): array
    {
        return $sales->groupBy('shop_id')
            ->map(fn ($group) => [
                'shop_name' => $group->first()->shop->name ?? 'Unknown',
                'transactions' => $group->count(),
                'revenue' => $group->sum('total'),
                'items_sold' => $group->sum(fn ($s) => $s->items->sum('quantity')),
            ])
            ->sortByDesc('revenue')
            ->values()
            ->toArray();
    }

    /**
     * Get hourly sales distribution.
     */
    protected function getHourlySales(Carbon $date, ?int $shopId = null): array
    {
        $query = Sale::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->selectRaw('EXTRACT(HOUR FROM created_at) as hour')
            ->selectRaw('COUNT(*) as transactions')
            ->selectRaw('SUM(total) as revenue')
            ->groupBy(DB::raw('EXTRACT(HOUR FROM created_at)'))
            ->orderBy('hour');

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $hourlyData = $query->get()->keyBy('hour');

        // Fill all 24 hours
        $result = [];
        for ($h = 0; $h < 24; $h++) {
            $result[$h] = [
                'hour' => sprintf('%02d:00', $h),
                'transactions' => (int) ($hourlyData[$h]->transactions ?? 0),
                'revenue' => (float) ($hourlyData[$h]->revenue ?? 0),
            ];
        }

        return $result;
    }
}
