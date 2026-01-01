<?php

namespace App\Livewire\Reports;

use App\Models\EggCategory;
use App\Models\Sale;
use App\Models\Shop;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Sales Summary')]
class SalesSummary extends Component
{
    #[Url]
    public ?int $shopId = null;

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $groupBy = 'date';

    public function mount(): void
    {
        $user = auth()->user();
        $this->shopId = $user?->shop_id;
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    /**
     * Get shops for filter.
     */
    #[Computed]
    public function shops(): Collection
    {
        return Shop::orderBy('name')->get();
    }

    /**
     * Get overall summary stats.
     */
    #[Computed]
    public function summary(): array
    {
        $baseQuery = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('sold_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('sold_at', '<=', $this->dateTo));

        $totalSales = (clone $baseQuery)->sum('total');
        $totalTransactions = (clone $baseQuery)->count();
        $totalDiscount = (clone $baseQuery)->sum('discount');
        $totalTax = (clone $baseQuery)->sum('tax');

        $byPayment = (clone $baseQuery)
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        return [
            'total_sales' => $totalSales,
            'total_transactions' => $totalTransactions,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'average_sale' => $totalTransactions > 0 ? $totalSales / $totalTransactions : 0,
            'by_payment' => $byPayment,
        ];
    }

    /**
     * Get sales by date.
     */
    #[Computed]
    public function salesByDate(): Collection
    {
        return Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('sold_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('sold_at', '<=', $this->dateTo))
            ->selectRaw('DATE(sold_at) as date, COUNT(*) as count, SUM(total) as total, SUM(discount) as discount')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Get sales by category.
     */
    #[Computed]
    public function salesByCategory(): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('egg_categories', 'sale_items.egg_category_id', '=', 'egg_categories.id')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->when($this->shopId, fn($q) => $q->where('sales.shop_id', $this->shopId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('sales.sold_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('sales.sold_at', '<=', $this->dateTo))
            ->selectRaw('egg_categories.id, egg_categories.name, egg_categories.code, 
                SUM(sale_items.quantity) as quantity, SUM(sale_items.line_total) as total')
            ->groupBy('egg_categories.id', 'egg_categories.name', 'egg_categories.code')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Get sales by payment method.
     */
    #[Computed]
    public function salesByPayment(): Collection
    {
        return Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('sold_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('sold_at', '<=', $this->dateTo))
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * Export to PDF.
     */
    public function exportPdf()
    {
        $data = [
            'summary' => $this->summary,
            'salesByDate' => $this->salesByDate,
            'salesByCategory' => $this->salesByCategory,
            'salesByPayment' => $this->salesByPayment,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'shop' => $this->shopId ? Shop::find($this->shopId) : null,
            'generatedAt' => now(),
        ];

        $pdf = Pdf::loadView('reports.sales-summary-pdf', $data);
        
        $filename = 'sales-summary-' . now()->format('Y-m-d-His') . '.pdf';
        
        return response()->streamDownload(
            fn() => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Clear filters.
     */
    public function clearFilters(): void
    {
        $this->shopId = null;
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.reports.sales-summary');
    }
}
