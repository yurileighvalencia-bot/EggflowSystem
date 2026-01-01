<?php

namespace App\Livewire\Reservations;

use App\Events\ReservationCreated;
use App\Models\EggCategory;
use App\Models\Inventory;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Create Reservation')]
class CreateReservation extends Component
{
    public ?int $shopId = null;
    public ?int $customerId = null;
    public string $customerSearch = '';
    public string $pickupDate = '';
    public string $pickupTime = '';
    public string $notes = '';

    public array $items = [];
    public bool $showCustomerDropdown = false;

    protected $rules = [
        'shopId' => 'required|exists:shops,id',
        'customerId' => 'nullable|exists:users,id',
        'pickupDate' => 'required|date|after_or_equal:today',
        'pickupTime' => 'nullable|date_format:H:i',
        'items' => 'required|array|min:1',
        'items.*.category_id' => 'required|exists:egg_categories,id',
        'items.*.quantity' => 'required|integer|min:1',
    ];

    protected $messages = [
        'items.required' => 'Please add at least one item.',
        'items.min' => 'Please add at least one item.',
    ];

    public function mount(): void
    {
        $user = auth()->user();
        $this->shopId = $user?->shop_id ?? Shop::first()?->id;
        $this->pickupDate = now()->addDay()->format('Y-m-d');
    }

    /**
     * Get shops for dropdown.
     */
    #[Computed]
    public function shops(): Collection
    {
        return Shop::orderBy('name')->get();
    }

    /**
     * Get available categories with stock.
     */
    #[Computed]
    public function availableCategories(): Collection
    {
        if (!$this->shopId) {
            return collect();
        }

        return EggCategory::query()
            ->where('is_active', true)
            ->whereHas('inventories', function ($q) {
                $q->where('shop_id', $this->shopId)
                    ->whereRaw('available_stock - reserved_stock > 0');
            })
            ->withSum(['inventories as available_stock' => fn($q) => $q->where('shop_id', $this->shopId)], 'available_stock')
            ->withSum(['inventories as reserved_stock' => fn($q) => $q->where('shop_id', $this->shopId)], 'reserved_stock')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($category) {
                $category->reservable_stock = $category->available_stock - $category->reserved_stock;
                return $category;
            });
    }

    /**
     * Search customers.
     */
    #[Computed]
    public function customers(): Collection
    {
        if (strlen($this->customerSearch) < 2) {
            return collect();
        }

        return User::query()
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->customerSearch}%")
                    ->orWhere('email', 'like', "%{$this->customerSearch}%")
                    ->orWhere('phone', 'like', "%{$this->customerSearch}%");
            })
            ->whereHas('roles', fn($q) => $q->where('name', 'customer'))
            ->limit(10)
            ->get();
    }

    /**
     * Select a customer.
     */
    public function selectCustomer(int $id): void
    {
        $customer = User::find($id);
        if ($customer) {
            $this->customerId = $customer->id;
            $this->customerSearch = $customer->name;
            $this->showCustomerDropdown = false;
        }
    }

    /**
     * Clear customer selection.
     */
    public function clearCustomer(): void
    {
        $this->customerId = null;
        $this->customerSearch = '';
    }

    /**
     * Update customer search.
     */
    public function updatedCustomerSearch(): void
    {
        $this->customerId = null;
        $this->showCustomerDropdown = strlen($this->customerSearch) >= 2;
        unset($this->customers);
    }

    /**
     * Add item to reservation.
     */
    public function addItem(int $categoryId): void
    {
        $category = EggCategory::find($categoryId);
        if (!$category) {
            return;
        }

        // Get available inventory
        $inventory = Inventory::where('shop_id', $this->shopId)
            ->where('egg_category_id', $categoryId)
            ->whereRaw('available_stock - reserved_stock > 0')
            ->first();

        if (!$inventory) {
            session()->flash('error', "No reservable stock for {$category->name}");
            return;
        }

        $price = $inventory->unit_price ?? $category->default_price;

        // Check if already in items
        foreach ($this->items as $key => $item) {
            if ($item['category_id'] === $categoryId) {
                $this->items[$key]['quantity']++;
                $this->items[$key]['subtotal'] = $this->items[$key]['quantity'] * $this->items[$key]['price'];
                return;
            }
        }

        // Add new item
        $this->items[] = [
            'category_id' => $categoryId,
            'name' => $category->name,
            'code' => $category->code,
            'price' => $price,
            'quantity' => 1,
            'subtotal' => $price,
        ];
    }

    /**
     * Update item quantity.
     */
    public function updateQuantity(int $index, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($index);
            return;
        }

        // Check available stock
        $categoryId = $this->items[$index]['category_id'];
        $reservableStock = Inventory::where('shop_id', $this->shopId)
            ->where('egg_category_id', $categoryId)
            ->selectRaw('SUM(available_stock - reserved_stock) as reservable')
            ->value('reservable');

        if ($quantity > $reservableStock) {
            session()->flash('error', "Only {$reservableStock} reservable for {$this->items[$index]['name']}");
            return;
        }

        $this->items[$index]['quantity'] = $quantity;
        $this->items[$index]['subtotal'] = $quantity * $this->items[$index]['price'];
    }

    /**
     * Remove item from reservation.
     */
    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /**
     * Calculate subtotal.
     */
    #[Computed]
    public function subtotal(): float
    {
        return collect($this->items)->sum('subtotal');
    }

    /**
     * Calculate total.
     */
    #[Computed]
    public function total(): float
    {
        return $this->subtotal;
    }

    /**
     * Create the reservation.
     */
    public function createReservation(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {
                // Create reservation
                $reservation = Reservation::create([
                    'shop_id' => $this->shopId,
                    'customer_id' => $this->customerId,
                    'status' => Reservation::STATUS_PENDING,
                    'subtotal' => $this->subtotal,
                    'tax' => 0, // Can be calculated based on shop settings
                    'total' => $this->total,
                    'pickup_date' => $this->pickupDate,
                    'pickup_time' => $this->pickupTime ?: null,
                    'notes' => $this->notes,
                ]);

                // Create items and reserve inventory
                foreach ($this->items as $item) {
                    // Reserve from inventory using FIFO
                    $remainingQty = $item['quantity'];
                    $inventories = Inventory::where('shop_id', $this->shopId)
                        ->where('egg_category_id', $item['category_id'])
                        ->whereRaw('available_stock - reserved_stock > 0')
                        ->fifo()
                        ->lockForUpdate()
                        ->get();

                    foreach ($inventories as $inventory) {
                        if ($remainingQty <= 0) break;

                        $reservable = $inventory->available_stock - $inventory->reserved_stock;
                        $reserveQty = min($remainingQty, $reservable);

                        // Create reservation item for this batch
                        ReservationItem::create([
                            'reservation_id' => $reservation->id,
                            'egg_category_id' => $item['category_id'],
                            'batch_id' => $inventory->batch_id,
                            'quantity' => $reserveQty,
                            'unit_price' => $item['price'],
                            'line_total' => $reserveQty * $item['price'],
                        ]);

                        // Update reserved stock
                        $inventory->reserved_stock += $reserveQty;
                        $inventory->save();

                        $remainingQty -= $reserveQty;
                    }

                    if ($remainingQty > 0) {
                        throw new \Exception("Insufficient stock for {$item['name']}");
                    }
                }

                // Fire event
                event(new ReservationCreated($reservation));
            });

            session()->flash('success', 'Reservation created successfully!');
            $this->redirect(route('reservations.index'));

        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.reservations.create-reservation');
    }
}
