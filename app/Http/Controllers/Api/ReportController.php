<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reports\SalesReportService;
use App\Services\Reports\InventoryReportService;
use App\Services\Reports\WastageReportService;
use App\Services\Reports\CollectionSalesComparisonService;
use App\Services\Reports\PdfReportService;
use App\Models\Sale;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(
        protected SalesReportService $salesService,
        protected InventoryReportService $inventoryService,
        protected WastageReportService $wastageService,
        protected CollectionSalesComparisonService $comparisonService,
        protected PdfReportService $pdfService
    ) {}

    /**
     * Get daily sales report.
     */
    public function dailySales(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $request->validate([
            'date' => 'nullable|date',
            'shop_id' => 'nullable|integer|exists:shops,id',
        ]);

        $date = $request->filled('date') 
            ? Carbon::parse($request->date) 
            : today();
        
        $shopId = $request->input('shop_id');

        $data = $this->salesService->getDailySummary($date, $shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get weekly sales report.
     */
    public function weeklySales(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $request->validate([
            'week_start' => 'nullable|date',
            'shop_id' => 'nullable|integer|exists:shops,id',
        ]);

        $weekStart = $request->filled('week_start') 
            ? Carbon::parse($request->week_start)->startOfWeek() 
            : now()->startOfWeek();
        
        $shopId = $request->input('shop_id');

        $data = $this->salesService->getWeeklyReport($shopId, $weekStart);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get monthly sales report.
     */
    public function monthlySales(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2099',
            'month' => 'nullable|integer|min:1|max:12',
            'shop_id' => 'nullable|integer|exists:shops,id',
        ]);

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $shopId = $request->input('shop_id');

        $data = $this->salesService->getMonthlyReport($year, $month, $shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get sales for custom date range.
     */
    public function salesDateRange(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'shop_id' => 'nullable|exists:shops,id',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $shopId = $request->input('shop_id');

        $data = $this->salesService->getDateRangeReport($startDate, $endDate, $shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get top selling categories.
     */
    public function topCategories(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : now()->startOfMonth();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date) 
            : now();

        $shopId = $request->input('shop_id');
        $limit = $request->input('limit', 10);

        $data = $this->salesService->getTopCategories($startDate, $endDate, $shopId, $limit);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get current inventory snapshot.
     */
    public function inventorySnapshot(Request $request): JsonResponse
    {
        $this->authorize('view-inventory');

        $shopId = $request->input('shop_id');
        $data = $this->inventoryService->getCurrentSnapshot($shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get low stock report.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $this->authorize('view-inventory');

        $shopId = $request->input('shop_id');
        $data = $this->inventoryService->getLowStockReport($shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get expiring inventory report.
     */
    public function expiringInventory(Request $request): JsonResponse
    {
        $this->authorize('view-inventory');

        $withinDays = $request->input('within_days', 7);
        $shopId = $request->input('shop_id');

        $data = $this->inventoryService->getExpiringReport($withinDays, $shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get inventory movement report.
     */
    public function inventoryMovement(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $shopId = $request->input('shop_id');

        $data = $this->inventoryService->getMovementReport($startDate, $endDate, $shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get stock valuation report.
     */
    public function stockValuation(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $shopId = $request->input('shop_id');
        $data = $this->inventoryService->getValuationReport($shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get wastage summary report.
     */
    public function wastageSummary(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : now()->startOfMonth();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date) 
            : now();

        $shopId = $request->input('shop_id');

        $data = $this->wastageService->getSummary($startDate, $endDate, $shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get wastage trends.
     */
    public function wastageTrends(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $months = $request->input('months', 6);
        $shopId = $request->input('shop_id');

        $data = $this->wastageService->getTrends($months, $shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get wastage by source breakdown.
     */
    public function wastageBySource(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : now()->startOfMonth();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date) 
            : now();

        $shopId = $request->input('shop_id');

        $data = $this->wastageService->getBySourceReport($startDate, $endDate, $shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get delivery discrepancy analysis.
     */
    public function discrepancyAnalysis(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : now()->startOfMonth();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date) 
            : now();

        $data = $this->wastageService->getDiscrepancyAnalysis($startDate, $endDate);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get collection efficiency report.
     */
    public function collectionEfficiency(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : now()->startOfMonth();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date) 
            : now();

        $farmId = $request->input('farm_id');

        $data = $this->wastageService->getCollectionEfficiencyReport($startDate, $endDate, $farmId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get collection vs sales comparison.
     */
    public function collectionSalesComparison(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : now()->startOfMonth();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date) 
            : now();

        $farmId = $request->input('farm_id');
        $shopId = $request->input('shop_id');

        $data = $this->comparisonService->getComparison($startDate, $endDate, $farmId, $shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get weekly comparison.
     */
    public function weeklyComparison(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $shopId = $request->input('shop_id');
        $data = $this->comparisonService->getWeeklyComparison($shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get monthly trend for a year.
     */
    public function monthlyTrend(Request $request): JsonResponse
    {
        $this->authorize('view-reports');

        $year = $request->input('year', now()->year);
        $shopId = $request->input('shop_id');

        $data = $this->comparisonService->getMonthlyTrend($year, $shopId);

        return response()->json([
            'data' => $data,
        ]);
    }

    // ==================== PDF EXPORTS ====================

    /**
     * Download daily sales PDF.
     */
    public function downloadDailySales(Request $request)
    {
        $this->authorize('view-reports');

        $date = $request->filled('date') 
            ? Carbon::parse($request->date) 
            : today();
        
        $shopId = $request->input('shop_id');

        return $this->pdfService->generateDailySalesReport($date, $shopId);
    }

    /**
     * Download weekly sales PDF.
     */
    public function downloadWeeklySales(Request $request)
    {
        $this->authorize('view-reports');

        $weekStart = $request->filled('week_start') 
            ? Carbon::parse($request->week_start) 
            : null;
        
        $shopId = $request->input('shop_id');

        return $this->pdfService->generateWeeklySalesReport($shopId, $weekStart);
    }

    /**
     * Download monthly sales PDF.
     */
    public function downloadMonthlySales(Request $request)
    {
        $this->authorize('view-reports');

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $shopId = $request->input('shop_id');

        return $this->pdfService->generateMonthlySalesReport($year, $month, $shopId);
    }

    /**
     * Download inventory snapshot PDF.
     */
    public function downloadInventorySnapshot(Request $request)
    {
        $this->authorize('view-inventory');

        $shopId = $request->input('shop_id');
        return $this->pdfService->generateInventoryReport($shopId);
    }

    /**
     * Download low stock PDF.
     */
    public function downloadLowStock(Request $request)
    {
        $this->authorize('view-inventory');

        $shopId = $request->input('shop_id');
        return $this->pdfService->generateLowStockReport($shopId);
    }

    /**
     * Download expiring inventory PDF.
     */
    public function downloadExpiringInventory(Request $request)
    {
        $this->authorize('view-inventory');

        $withinDays = $request->input('within_days', 7);
        $shopId = $request->input('shop_id');

        return $this->pdfService->generateExpiringReport($withinDays, $shopId);
    }

    /**
     * Download wastage report PDF.
     */
    public function downloadWastageReport(Request $request)
    {
        $this->authorize('view-reports');

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : now()->startOfMonth();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date) 
            : now();

        $shopId = $request->input('shop_id');

        return $this->pdfService->generateWastageReport($startDate, $endDate, $shopId);
    }

    /**
     * Download collection vs sales comparison PDF.
     */
    public function downloadComparisonReport(Request $request)
    {
        $this->authorize('view-reports');

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date) 
            : now()->startOfMonth();
        
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date) 
            : now();

        $farmId = $request->input('farm_id');
        $shopId = $request->input('shop_id');

        return $this->pdfService->generateComparisonReport($startDate, $endDate, $farmId, $shopId);
    }

    /**
     * Download sale receipt PDF.
     */
    public function downloadSaleReceipt(Sale $sale)
    {
        $this->authorize('view', $sale);

        return $this->pdfService->generateSaleReceipt($sale);
    }

    /**
     * Download delivery manifest PDF.
     */
    public function downloadDeliveryManifest(Delivery $delivery)
    {
        $this->authorize('view', $delivery);

        return $this->pdfService->generateDeliveryManifest($delivery);
    }
}
