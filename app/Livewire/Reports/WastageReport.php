<?php

namespace App\Livewire\Reports;

use App\Models\Shop;
use App\Services\Reports\WastageReportService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Wastage Report')]
class WastageReport extends Component
{
    public ?int $shopId = null;
    public string $startDate;
    public string $endDate;
    public string $view = 'summary'; // summary, by-source, trends, discrepancies
    public int $trendMonths = 6;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    #[Computed]
    public function shops()
    {
        return Shop::orderBy('name')->get();
    }

    #[Computed]
    public function summary()
    {
        $service = app(WastageReportService::class);
        
        return $service->getSummary(
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate),
            $this->shopId
        );
    }

    #[Computed]
    public function bySource()
    {
        $service = app(WastageReportService::class);
        
        return $service->getBySourceReport(
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate),
            $this->shopId
        );
    }

    #[Computed]
    public function trends()
    {
        $service = app(WastageReportService::class);
        
        return $service->getTrends($this->trendMonths, $this->shopId);
    }

    #[Computed]
    public function discrepancies()
    {
        $service = app(WastageReportService::class);
        
        return $service->getDiscrepancyAnalysis(
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate)
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
            'summary' => $this->summary,
            'bySource' => $this->bySource,
            'trends' => $this->trends,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'shopId' => $this->shopId,
            'shopName' => $this->shopId ? Shop::find($this->shopId)->name : 'All Shops',
        ];
        
        $pdf = $service->generateWastageReport($data);
        
        return response()->streamDownload(
            fn () => print($pdf->output()),
            'wastage-report-' . now()->format('Y-m-d') . '.pdf'
        );
    }

    public function render()
    {
        return view('livewire.reports.wastage-report');
    }
}
