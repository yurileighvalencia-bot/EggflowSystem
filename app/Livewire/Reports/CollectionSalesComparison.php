<?php

namespace App\Livewire\Reports;

use App\Models\Farm;
use App\Models\Shop;
use App\Services\Reports\CollectionSalesComparisonService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Collection vs Sales')]
class CollectionSalesComparison extends Component
{
    public ?int $farmId = null;
    public ?int $shopId = null;
    public string $startDate;
    public string $endDate;
    public string $view = 'overview'; // overview, by-category, daily-trend

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    #[Computed]
    public function farms()
    {
        return Farm::orderBy('name')->get();
    }

    #[Computed]
    public function shops()
    {
        return Shop::orderBy('name')->get();
    }

    #[Computed]
    public function comparison()
    {
        $service = app(CollectionSalesComparisonService::class);
        
        return $service->getComparison(
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate),
            $this->farmId,
            $this->shopId
        );
    }

    public function setPreset(string $preset): void
    {
        switch ($preset) {
            case 'today':
                $this->startDate = now()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'week':
                $this->startDate = now()->startOfWeek()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'month':
                $this->startDate = now()->startOfMonth()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'quarter':
                $this->startDate = now()->startOfQuarter()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'year':
                $this->startDate = now()->startOfYear()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
        }
    }

    public function exportPdf(): mixed
    {
        $service = app(\App\Services\Reports\PdfReportService::class);
        
        $data = [
            'comparison' => $this->comparison,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'farmId' => $this->farmId,
            'shopId' => $this->shopId,
            'farmName' => $this->farmId ? Farm::find($this->farmId)->name : 'All Farms',
            'shopName' => $this->shopId ? Shop::find($this->shopId)->name : 'All Shops',
        ];
        
        $pdf = $service->generateCollectionSalesReport($data);
        
        return response()->streamDownload(
            fn () => print($pdf->output()),
            'collection-vs-sales-' . now()->format('Y-m-d') . '.pdf'
        );
    }

    public function render()
    {
        return view('livewire.reports.collection-sales-comparison');
    }
}
