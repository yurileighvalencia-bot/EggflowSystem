<?php

namespace App\Livewire\Reports;

use App\Models\Shift;
use App\Models\Shop;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.dashboard')]
#[Title('Shift Report')]
class ShiftReport extends Component
{
    use WithPagination;

    #[Url]
    public ?int $shopId = null;

    #[Url]
    public ?int $userId = null;

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $discrepancyFilter = '';

    public ?Shift $selectedShift = null;
    public bool $showDetailModal = false;

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
     * Get staff for filter.
     */
    #[Computed]
    public function staff(): Collection
    {
        return User::query()
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['shop_staff', 'manager']))
            ->orderBy('name')
            ->get();
    }

    /**
     * Get summary stats.
     */
    #[Computed]
    public function summary(): array
    {
        $baseQuery = Shift::query()
            ->where('status', 'closed')
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('opened_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('opened_at', '<=', $this->dateTo));

        $totalShifts = (clone $baseQuery)->count();
        $shiftsWithDiscrepancy = (clone $baseQuery)->where('discrepancy', '!=', 0)->count();
        $totalShortage = (clone $baseQuery)->where('discrepancy', '<', 0)->sum('discrepancy');
        $totalOverage = (clone $baseQuery)->where('discrepancy', '>', 0)->sum('discrepancy');

        return [
            'total_shifts' => $totalShifts,
            'with_discrepancy' => $shiftsWithDiscrepancy,
            'discrepancy_rate' => $totalShifts > 0 ? ($shiftsWithDiscrepancy / $totalShifts) * 100 : 0,
            'total_shortage' => $totalShortage,
            'total_overage' => $totalOverage,
            'net_discrepancy' => $totalShortage + $totalOverage,
        ];
    }

    /**
     * Get paginated shifts.
     */
    #[Computed]
    public function shifts()
    {
        return Shift::query()
            ->with(['user', 'shop', 'adjustments'])
            ->where('status', 'closed')
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('opened_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('opened_at', '<=', $this->dateTo))
            ->when($this->discrepancyFilter === 'shortage', fn($q) => $q->where('discrepancy', '<', 0))
            ->when($this->discrepancyFilter === 'overage', fn($q) => $q->where('discrepancy', '>', 0))
            ->when($this->discrepancyFilter === 'balanced', fn($q) => $q->where('discrepancy', 0))
            ->orderByDesc('closed_at')
            ->paginate(15);
    }

    /**
     * View shift details.
     */
    public function viewDetails(int $id): void
    {
        $this->selectedShift = Shift::with(['user', 'shop', 'adjustments.user', 'sales'])->find($id);
        $this->showDetailModal = true;
    }

    /**
     * Close detail modal.
     */
    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedShift = null;
    }

    /**
     * Export to PDF.
     */
    public function exportPdf()
    {
        $shifts = Shift::query()
            ->with(['user', 'shop'])
            ->where('status', 'closed')
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('opened_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('opened_at', '<=', $this->dateTo))
            ->when($this->discrepancyFilter === 'shortage', fn($q) => $q->where('discrepancy', '<', 0))
            ->when($this->discrepancyFilter === 'overage', fn($q) => $q->where('discrepancy', '>', 0))
            ->when($this->discrepancyFilter === 'balanced', fn($q) => $q->where('discrepancy', 0))
            ->orderByDesc('closed_at')
            ->get();

        $data = [
            'shifts' => $shifts,
            'summary' => $this->summary,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'shop' => $this->shopId ? Shop::find($this->shopId) : null,
            'generatedAt' => now(),
        ];

        $pdf = Pdf::loadView('reports.shift-report-pdf', $data);
        
        $filename = 'shift-report-' . now()->format('Y-m-d-His') . '.pdf';
        
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
        $this->userId = null;
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->discrepancyFilter = '';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.reports.shift-report');
    }
}
