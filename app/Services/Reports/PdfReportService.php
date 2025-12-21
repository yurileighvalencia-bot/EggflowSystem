<?php

namespace App\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Http\Response;

class PdfReportService
{
    protected SalesReportService $salesService;
    protected InventoryReportService $inventoryService;
    protected WastageReportService $wastageService;
    protected CollectionSalesComparisonService $comparisonService;

    public function __construct(
        SalesReportService $salesService,
        InventoryReportService $inventoryService,
        WastageReportService $wastageService,
        CollectionSalesComparisonService $comparisonService
    ) {
        $this->salesService = $salesService;
        $this->inventoryService = $inventoryService;
        $this->wastageService = $wastageService;
        $this->comparisonService = $comparisonService;
    }

    /**
     * Generate daily sales report PDF.
     */
    public function generateDailySalesReport(Carbon $date, ?int $shopId = null): Response
    {
        $data = $this->salesService->getDailySummary($date, $shopId);
        $data['report_title'] = 'Daily Sales Report';
        $data['generated_at'] = now()->format('F j, Y g:i A');

        $pdf = Pdf::loadView('reports.sales.daily', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("daily-sales-report-{$date->format('Y-m-d')}.pdf");
    }

    /**
     * Generate weekly sales report PDF.
     */
    public function generateWeeklySalesReport(?int $shopId = null, ?Carbon $weekStart = null): Response
    {
        $data = $this->salesService->getWeeklyReport($shopId, $weekStart);
        $data['report_title'] = 'Weekly Sales Report';
        $data['generated_at'] = now()->format('F j, Y g:i A');

        $pdf = Pdf::loadView('reports.sales.weekly', $data);
        $pdf->setPaper('a4', 'portrait');

        $weekStartStr = ($weekStart ?? now()->startOfWeek())->format('Y-m-d');
        return $pdf->download("weekly-sales-report-{$weekStartStr}.pdf");
    }

    /**
     * Generate monthly sales report PDF.
     */
    public function generateMonthlySalesReport(int $year, int $month, ?int $shopId = null): Response
    {
        $data = $this->salesService->getMonthlyReport($year, $month, $shopId);
        $data['report_title'] = 'Monthly Sales Report';
        $data['generated_at'] = now()->format('F j, Y g:i A');

        $pdf = Pdf::loadView('reports.sales.monthly', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("monthly-sales-report-{$year}-{$month}.pdf");
    }

    /**
     * Generate inventory snapshot PDF.
     */
    public function generateInventoryReport(?int $shopId = null): Response
    {
        $data = $this->inventoryService->getCurrentSnapshot($shopId);
        $data['report_title'] = 'Inventory Snapshot Report';

        $pdf = Pdf::loadView('reports.inventory.snapshot', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("inventory-report-" . now()->format('Y-m-d') . ".pdf");
    }

    /**
     * Generate low stock alert PDF.
     */
    public function generateLowStockReport(?int $shopId = null): Response
    {
        $data = $this->inventoryService->getLowStockReport($shopId);
        $data['report_title'] = 'Low Stock Alert Report';

        $pdf = Pdf::loadView('reports.inventory.low-stock', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("low-stock-report-" . now()->format('Y-m-d') . ".pdf");
    }

    /**
     * Generate expiring inventory PDF.
     */
    public function generateExpiringReport(int $withinDays = 7, ?int $shopId = null): Response
    {
        $data = $this->inventoryService->getExpiringReport($withinDays, $shopId);
        $data['report_title'] = 'Expiring Inventory Report';

        $pdf = Pdf::loadView('reports.inventory.expiring', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("expiring-inventory-report-" . now()->format('Y-m-d') . ".pdf");
    }

    /**
     * Generate wastage analysis PDF.
     */
    public function generateWastageReport(Carbon $startDate, Carbon $endDate, ?int $shopId = null): Response
    {
        $data = $this->wastageService->getSummary($startDate, $endDate, $shopId);
        $data['report_title'] = 'Wastage Analysis Report';

        $pdf = Pdf::loadView('reports.wastage.summary', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("wastage-report-{$startDate->format('Y-m-d')}-to-{$endDate->format('Y-m-d')}.pdf");
    }

    /**
     * Generate collection vs sales comparison PDF.
     */
    public function generateComparisonReport(Carbon $startDate, Carbon $endDate, ?int $farmId = null, ?int $shopId = null): Response
    {
        $data = $this->comparisonService->getComparison($startDate, $endDate, $farmId, $shopId);
        $data['report_title'] = 'Collection vs Sales Comparison Report';

        $pdf = Pdf::loadView('reports.comparison.collection-sales', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("collection-sales-comparison-{$startDate->format('Y-m-d')}-to-{$endDate->format('Y-m-d')}.pdf");
    }

    /**
     * Generate sale receipt PDF.
     */
    public function generateSaleReceipt($sale): Response
    {
        $sale->load(['shop', 'customer', 'items.eggCategory', 'cashier']);

        $data = [
            'sale' => $sale,
            'report_title' => 'Sale Receipt',
        ];

        $pdf = Pdf::loadView('reports.receipts.sale', $data);
        $pdf->setPaper([0, 0, 226.77, 500], 'portrait'); // 80mm receipt width

        return $pdf->download("receipt-{$sale->sale_number}.pdf");
    }

    /**
     * Generate delivery manifest PDF.
     */
    public function generateDeliveryManifest($delivery): Response
    {
        $delivery->load(['farm', 'shop', 'items.batch.eggCategory', 'dispatchedBy']);

        $data = [
            'delivery' => $delivery,
            'report_title' => 'Delivery Manifest',
        ];

        $pdf = Pdf::loadView('reports.delivery.manifest', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("delivery-manifest-{$delivery->delivery_number}.pdf");
    }

    /**
     * Stream PDF instead of download.
     */
    public function streamPdf(string $view, array $data, string $paper = 'a4', string $orientation = 'portrait')
    {
        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper($paper, $orientation);

        return $pdf->stream();
    }

    /**
     * Get PDF as base64 string (for API responses).
     */
    public function getPdfBase64(string $view, array $data, string $paper = 'a4', string $orientation = 'portrait'): string
    {
        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper($paper, $orientation);

        return base64_encode($pdf->output());
    }
}
