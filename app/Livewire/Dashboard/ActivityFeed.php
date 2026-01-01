<?php

namespace App\Livewire\Dashboard;

use App\Models\DailyCollection;
use App\Models\Delivery;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\WastageLog;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use OwenIt\Auditing\Models\Audit;

#[Layout('components.layouts.dashboard')]
#[Title('Activity Feed')]
class ActivityFeed extends Component
{
    #[Url]
    public string $filter = 'all';

    #[Url]
    public int $limit = 50;

    public bool $showDetailModal = false;
    public ?Audit $selectedAudit = null;

    protected array $filterTypes = [
        'all' => 'All Activity',
        'sales' => 'Sales',
        'reservations' => 'Reservations',
        'deliveries' => 'Deliveries',
        'collections' => 'Collections',
        'wastage' => 'Wastage',
    ];

    protected array $modelMap = [
        'sales' => Sale::class,
        'reservations' => Reservation::class,
        'deliveries' => Delivery::class,
        'collections' => DailyCollection::class,
        'wastage' => WastageLog::class,
    ];

    public function render()
    {
        return view('livewire.dashboard.activity-feed');
    }

    #[Computed]
    public function activities(): Collection
    {
        $query = Audit::with(['user', 'auditable'])
            ->orderByDesc('created_at');

        if ($this->filter !== 'all' && isset($this->modelMap[$this->filter])) {
            $query->where('auditable_type', $this->modelMap[$this->filter]);
        } elseif ($this->filter === 'all') {
            // Only show specific model types for "all"
            $query->whereIn('auditable_type', array_values($this->modelMap));
        }

        return $query->limit($this->limit)->get()->map(function ($audit) {
            return [
                'id' => $audit->id,
                'event' => $audit->event,
                'type' => class_basename($audit->auditable_type),
                'auditable_id' => $audit->auditable_id,
                'user_name' => $audit->user?->name ?? 'System',
                'user_id' => $audit->user_id,
                'description' => $this->formatDescription($audit),
                'icon' => $this->getIcon($audit),
                'color' => $this->getColor($audit),
                'time' => $audit->created_at,
                'time_diff' => $audit->created_at->diffForHumans(),
                'audit' => $audit,
            ];
        });
    }

    #[Computed]
    public function recentStats(): array
    {
        $today = now()->startOfDay();

        return [
            'sales_today' => Sale::where('created_at', '>=', $today)->count(),
            'reservations_today' => Reservation::where('created_at', '>=', $today)->count(),
            'deliveries_today' => Delivery::where('created_at', '>=', $today)->count(),
            'collections_today' => DailyCollection::where('created_at', '>=', $today)->count(),
        ];
    }

    public function getFilterTypes(): array
    {
        return $this->filterTypes;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function viewDetails(int $auditId): void
    {
        $this->selectedAudit = Audit::with(['user', 'auditable'])->find($auditId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedAudit = null;
    }

    public function loadMore(): void
    {
        $this->limit += 50;
    }

    protected function formatDescription(Audit $audit): string
    {
        $type = class_basename($audit->auditable_type);
        $event = ucfirst($audit->event);

        switch ($type) {
            case 'Sale':
                $total = $audit->new_values['total'] ?? $audit->old_values['total'] ?? null;
                if ($audit->event === 'created') {
                    return "New sale created" . ($total ? " - ₱" . number_format($total, 2) : '');
                } elseif ($audit->event === 'updated') {
                    $status = $audit->new_values['status'] ?? null;
                    if ($status) {
                        return "Sale marked as {$status}";
                    }
                    return "Sale updated";
                }
                return "{$event} sale";

            case 'Reservation':
                if ($audit->event === 'created') {
                    $customer = $audit->new_values['customer_name'] ?? 'Unknown';
                    return "New reservation by {$customer}";
                } elseif ($audit->event === 'updated') {
                    $status = $audit->new_values['status'] ?? null;
                    if ($status) {
                        return "Reservation marked as {$status}";
                    }
                    return "Reservation updated";
                }
                return "{$event} reservation";

            case 'Delivery':
                if ($audit->event === 'created') {
                    $quantity = $audit->new_values['quantity'] ?? 0;
                    return "Delivery created with {$quantity} eggs";
                } elseif ($audit->event === 'updated') {
                    $status = $audit->new_values['status'] ?? null;
                    if ($status === 'dispatched') {
                        return "Delivery dispatched";
                    } elseif ($status === 'received') {
                        return "Delivery received at shop";
                    }
                    return "Delivery updated";
                }
                return "{$event} delivery";

            case 'DailyCollection':
                if ($audit->event === 'created') {
                    $quantity = $audit->new_values['quantity'] ?? 0;
                    return "Collected {$quantity} eggs from farm";
                } elseif ($audit->event === 'updated') {
                    $verified = $audit->new_values['verified_at'] ?? null;
                    if ($verified) {
                        return "Collection verified";
                    }
                    return "Collection updated";
                }
                return "{$event} collection";

            case 'WastageLog':
                if ($audit->event === 'created') {
                    $quantity = $audit->new_values['quantity'] ?? 0;
                    $source = $audit->new_values['source'] ?? 'unknown';
                    return "Logged {$quantity} eggs as {$source} wastage";
                }
                return "{$event} wastage log";

            default:
                return "{$event} {$type}";
        }
    }

    protected function getIcon(Audit $audit): string
    {
        $type = class_basename($audit->auditable_type);

        return match ($type) {
            'Sale' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
            'Reservation' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
            'Delivery' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>',
            'DailyCollection' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>',
            'WastageLog' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
            default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        };
    }

    protected function getColor(Audit $audit): string
    {
        $type = class_basename($audit->auditable_type);

        return match ($type) {
            'Sale' => 'green',
            'Reservation' => 'blue',
            'Delivery' => 'purple',
            'DailyCollection' => 'amber',
            'WastageLog' => 'red',
            default => 'gray',
        };
    }
}
