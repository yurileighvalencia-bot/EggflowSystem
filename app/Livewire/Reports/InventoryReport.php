<?php

namespace App\Livewire\Reports;

use App\Models\EggCategory;
use App\Models\Inventory;
use App\Models\Shop;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Inventory Report')]
class InventoryReport extends Component
{
    #[Url]
    public ?int $shopId = null;

    #[Url]
    public string $view = 'snapshot'; // snapshot, low-stock, expiring, valuation

    #[Url]
    public int $expiringDays = 7;

    public function mount(): void
    {
        $user = auth()->user();
        $this->shopId = $user?->shop_id;
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
     * Get inventory snapshot.
     */
    #[Computed]
    public function snapshot(): array
    {
        $query = Inventory::with(['shop', 'batch.eggCategory', 'eggCategory'])
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->where(function($q) {
                $q->where('available_stock', '>', 0)
                  ->orWhere('reserved_stock', '>', 0);
            });

        $inventory = $query->get();

        // Group by category
        $byCategory = $inventory->groupBy('egg_category_id')->map(function ($items, $categoryId) {
            $category = $items->first()->eggCategory;
            return [
                'category_id' => $categoryId,
                'category_name' => $category->name ?? 'Unknown',
                'available_stock' => $items->sum('available_stock'),
                'reserved_stock' => $items->sum('reserved_stock'),
                'total_stock' => $items->sum('available_stock') + $items->sum('reserved_stock'),
                'batch_count' => $items->pluck('batch_id')->unique()->count(),
            ];
        })->sortByDesc('total_stock')->values();

        return [
            'total_available' => $inventory->sum('available_stock'),
            'total_reserved' => $inventory->sum('reserved_stock'),
            'total_stock' => $inventory->sum('available_stock') + $inventory->sum('reserved_stock'),
            'categories' => $byCategory,
        ];
    }

    /**
     * Get low stock items.
     */
    #[Computed]
    public function lowStockItems(): Collection
    {
        return EggCategory::query()
            ->where('is_active', true)
            ->withSum(['inventories as available_stock' => function($q) {
                $q->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId));
            }], 'available_stock')
            ->get()
            ->filter(fn ($cat) => ($cat->available_stock ?? 0) <= $cat->low_stock_threshold)
            ->sortBy('available_stock')
            ->values();
    }

    /**
     * Get expiring items.
     */
    #[Computed]
    public function expiringItems(): Collection
    {
        return Inventory::with(['shop', 'batch', 'eggCategory'])
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->whereHas('batch', function ($q) {
                $q->whereBetween('expires_at', [now(), now()->addDays($this->expiringDays)]);
            })
            ->where(function ($q) {
                $q->where('available_stock', '>', 0)
                  ->orWhere('reserved_stock', '>', 0);
            })
            ->get()
            ->map(fn ($inv) => [
                'shop_name' => $inv->shop->name,
                'category_name' => $inv->eggCategory->name,
                'batch_code' => $inv->batch->batch_code ?? 'N/A',
                'available_stock' => $inv->available_stock,
                'reserved_stock' => $inv->reserved_stock,
                'expires_at' => $inv->batch->expires_at,
                'days_left' => now()->diffInDays($inv->batch->expires_at, false),
            ])
            ->sortBy('days_left')
            ->values();
    }

    /**
     * Get stock valuation.
     */
    #[Computed]
    public function valuation(): array
    {
        $inventory = Inventory::with(['eggCategory', 'batch'])
            ->when($this->shopId, fn($q) => $q->where('shop_id', $this->shopId))
            ->where(function($q) {
                $q->where('available_stock', '>', 0)
                  ->orWhere('reserved_stock', '>', 0);
            })
            ->get();

        $byCategory = $inventory->groupBy('egg_category_id')->map(function ($items, $categoryId) {
            $category = $items->first()->eggCategory;
            $totalQty = $items->sum('available_stock') + $items->sum('reserved_stock');
            $avgPrice = $items->avg('unit_price') ?? $category->default_price ?? 0;
            
            return [
                'category_name' => $category->name ?? 'Unknown',
                'quantity' => $totalQty,
                'avg_price' => $avgPrice,
                'value' => $totalQty * $avgPrice,
            ];
        })->sortByDesc('value')->values();

        return [
            'total_value' => $byCategory->sum('value'),
            'total_quantity' => $byCategory->sum('quantity'),
            'categories' => $byCategory,
        ];
    }

    /**
     * Export to PDF.
     */
    public function exportPdf(): mixed
    {
        $data = [
            'title' => 'Inventory Report',
            'generated_at' => now(),
            'shop' => $this->shopId ? Shop::find($this->shopId)?->name : 'All Shops',
            'view' => $this->view,
            'snapshot' => $this->snapshot,
            'low_stock' => $this->lowStockItems,
            'expiring' => $this->expiringItems,
            'valuation' => $this->valuation,
        ];

        $pdf = Pdf::loadView('reports.pdf.inventory', $data);
        
        return response()->streamDownload(
            fn () => print($pdf->output()),
            'inventory-report-' . now()->format('Y-m-d') . '.pdf'
        );
    }

    public function render()
    {
        return view('livewire.reports.inventory-report');
    }
}
