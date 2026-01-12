<?php

namespace App\Livewire\POS;

use App\Events\LowStockDetected;
use App\Events\SaleCompleted;
use App\Events\StockUpdated;
use App\Exceptions\InsufficientStockException;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\EggCategory;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Models\Shop;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.dashboard')]
#[Title('Point of Sale')]
class PointOfSale extends Component
{
    use AuthorizesRequests;
    public ?int $shopId = null;
    public array $cart = [];
    public string $paymentMethod = 'cash';
    public float $discount = 0;
    public string $notes = '';
    public bool $showCheckoutModal = false;
    public float $amountTendered = 0;
    public ?Sale $lastSale = null;
    public bool $showReceiptModal = false;

    // Customer selection properties
    public string $customerSearch = '';
    public ?int $customerId = null;
    public ?Customer $selectedCustomer = null;
    public bool $showCustomerDropdown = false;
    public bool $showQuickAddCustomer = false;
    public string $newCustomerName = '';
    public string $newCustomerPhone = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->shopId = $user?->shop_id ?? Shop::first()?->id;
        
        // Restore cart from session
        $this->cart = session()->get('pos_cart_' . $this->shopId, []);
    }

    /**
     * Get the active shift for the current user.
     */
    #[Computed]
    public function activeShift(): ?Shift
    {
        $shiftId = session('active_shift_id');
        
        if ($shiftId) {
            $shift = Shift::where('id', $shiftId)
                ->where('status', 'open')
                ->first();
            
            if ($shift) {
                return $shift;
            }
            
            // Session has stale shift_id, clear it
            session()->forget('active_shift_id');
        }

        // Check if user has an open shift
        return auth()->user()?->currentShift();
    }

    /**
     * Check if POS is ready for transactions.
     */
    #[Computed]
    public function isReady(): bool
    {
        return $this->activeShift !== null;
    }

    /**
     * Handle shift opened event.
     */
    #[On('shiftOpened')]
    public function handleShiftOpened(): void
    {
        unset($this->activeShift);
        unset($this->isReady);
    }

    /**
     * Handle shift closed event.
     */
    #[On('shiftClosed')]
    public function handleShiftClosed(): void
    {
        unset($this->activeShift);
        unset($this->isReady);
    }

    /**
     * Handle adjustment recorded event.
     */
    #[On('adjustmentRecorded')]
    public function handleAdjustmentRecorded(): void
    {
        // Refresh shift data if needed
        unset($this->activeShift);
    }

    /**
     * Get the current shop.
     */
    #[Computed]
    public function shop(): ?Shop
    {
        return Shop::find($this->shopId);
    }

    /**
     * Search for customers based on query.
     */
    #[Computed]
    public function customerResults(): Collection
    {
        if (strlen($this->customerSearch) < 2) {
            return collect();
        }

        return Customer::active()
            ->search($this->customerSearch)
            ->limit(10)
            ->get();
    }

    /**
     * Update customer search and show dropdown.
     */
    public function updatedCustomerSearch(): void
    {
        $this->showCustomerDropdown = strlen($this->customerSearch) >= 2;
        $this->showQuickAddCustomer = false;
    }

    /**
     * Select a customer for the sale.
     */
    public function selectCustomer(int $customerId): void
    {
        $this->selectedCustomer = Customer::find($customerId);
        $this->customerId = $customerId;
        $this->customerSearch = $this->selectedCustomer?->display_name ?? '';
        $this->showCustomerDropdown = false;
        $this->showQuickAddCustomer = false;
    }

    /**
     * Clear selected customer (for walk-in sale).
     */
    public function clearCustomer(): void
    {
        $this->selectedCustomer = null;
        $this->customerId = null;
        $this->customerSearch = '';
        $this->showCustomerDropdown = false;
        $this->showQuickAddCustomer = false;
    }

    /**
     * Show quick add customer form.
     */
    public function showQuickAdd(): void
    {
        $this->showQuickAddCustomer = true;
        $this->showCustomerDropdown = false;
        $this->newCustomerName = $this->customerSearch;
        $this->newCustomerPhone = '';
    }

    /**
     * Create a new customer quickly and select them.
     */
    public function createQuickCustomer(): void
    {
        $this->validate([
            'newCustomerName' => 'required|min:3|max:255',
            'newCustomerPhone' => 'nullable|regex:/^[0-9\-\+\s]+$/|max:20',
        ], [
            'newCustomerName.required' => 'Customer name is required.',
            'newCustomerName.min' => 'Customer name must be at least 3 characters.',
            'newCustomerPhone.regex' => 'Please enter a valid phone number.',
        ]);

        $customer = Customer::create([
            'name' => $this->newCustomerName,
            'phone' => $this->newCustomerPhone ?: null,
        ]);

        $this->selectCustomer($customer->id);
        $this->showQuickAddCustomer = false;
        $this->newCustomerName = '';
        $this->newCustomerPhone = '';

        session()->flash('success', 'Customer "' . $customer->name . '" created and selected.');
    }

    /**
     * Get available categories with stock for this shop.
     */
    #[Computed]
    public function availableCategories(): Collection
    {
        if (!$this->shopId) {
            return collect();
        }

        return EggCategory::query()
            ->where('is_active', true)
            ->whereHas('inventories', function ($query) {
                $query->where('shop_id', $this->shopId)
                    ->where('available_stock', '>', 0);
            })
            ->withSum(['inventories as available_stock' => function ($query) {
                $query->where('shop_id', $this->shopId);
            }], 'available_stock')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get stock info for a category.
     */
    public function getCategoryStock(int $categoryId): int
    {
        return Inventory::where('shop_id', $this->shopId)
            ->where('egg_category_id', $categoryId)
            ->sum('available_stock');
    }

    /**
     * Add item to cart.
     */
    public function addToCart(int $categoryId, int $quantity = 1): void
    {
        $category = EggCategory::find($categoryId);
        if (!$category) {
            return;
        }

        $availableStock = $this->getCategoryStock($categoryId);
        $currentInCart = $this->cart[$categoryId]['quantity'] ?? 0;

        if ($currentInCart + $quantity > $availableStock) {
            session()->flash('error', "Only {$availableStock} available for {$category->name}");
            return;
        }

        // Get the price from inventory or category default
        $price = Inventory::where('shop_id', $this->shopId)
            ->where('egg_category_id', $categoryId)
            ->where('available_stock', '>', 0)
            ->value('unit_price') ?? $category->default_price;

        if (isset($this->cart[$categoryId])) {
            $this->cart[$categoryId]['quantity'] += $quantity;
            $this->cart[$categoryId]['subtotal'] = $this->cart[$categoryId]['quantity'] * $this->cart[$categoryId]['price'];
        } else {
            $this->cart[$categoryId] = [
                'category_id' => $categoryId,
                'name' => $category->name,
                'code' => $category->code,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $price * $quantity,
                'is_tax_exempt' => $category->is_tax_exempt,
            ];
        }

        $this->saveCart();
    }

    /**
     * Update cart item quantity.
     */
    public function updateQuantity(int $categoryId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeFromCart($categoryId);
            return;
        }

        $availableStock = $this->getCategoryStock($categoryId);
        if ($quantity > $availableStock) {
            session()->flash('error', "Only {$availableStock} available");
            return;
        }

        if (isset($this->cart[$categoryId])) {
            $this->cart[$categoryId]['quantity'] = $quantity;
            $this->cart[$categoryId]['subtotal'] = $quantity * $this->cart[$categoryId]['price'];
            $this->saveCart();
        }
    }

    /**
     * Remove item from cart.
     */
    public function removeFromCart(int $categoryId): void
    {
        unset($this->cart[$categoryId]);
        $this->saveCart();
    }

    /**
     * Clear the entire cart.
     */
    public function clearCart(): void
    {
        $this->cart = [];
        $this->discount = 0;
        $this->notes = '';
        $this->saveCart();
    }

    /**
     * Save cart to session.
     */
    private function saveCart(): void
    {
        session()->put('pos_cart_' . $this->shopId, $this->cart);
    }

    /**
     * Calculate cart subtotal.
     */
    #[Computed]
    public function subtotal(): float
    {
        return collect($this->cart)->sum('subtotal');
    }

    /**
     * Calculate tax amount.
     */
    #[Computed]
    public function tax(): float
    {
        $shop = $this->shop;
        if (!$shop || $shop->default_tax_rate <= 0) {
            return 0;
        }

        $taxableTotal = collect($this->cart)
            ->filter(fn ($item) => !$item['is_tax_exempt'])
            ->sum('subtotal');

        return round($taxableTotal * $shop->default_tax_rate, 2);
    }

    /**
     * Calculate total.
     */
    #[Computed]
    public function total(): float
    {
        return $this->subtotal + $this->tax - $this->discount;
    }

    /**
     * Calculate change due.
     */
    #[Computed]
    public function changeDue(): float
    {
        return max(0, $this->amountTendered - $this->total);
    }

    /**
     * Open checkout modal.
     */
    public function openCheckout(): void
    {
        if (empty($this->cart)) {
            session()->flash('error', 'Cart is empty');
            return;
        }

        // Block checkout without open shift
        if (!$this->activeShift) {
            session()->flash('error', 'Please open a shift before processing sales.');
            return;
        }

        $this->amountTendered = $this->total;
        $this->showCheckoutModal = true;
    }

    /**
     * Process the sale with FIFO stock deduction and transaction locking.
     */
    public function processSale(): void
    {
        $this->authorize('create-sale');

        if (empty($this->cart)) {
            session()->flash('error', 'Cart is empty');
            return;
        }

        if ($this->amountTendered < $this->total) {
            session()->flash('error', 'Amount tendered is less than total');
            return;
        }

        // Require active shift
        if (!$this->activeShift) {
            session()->flash('error', 'No active shift. Please open a shift first.');
            $this->showCheckoutModal = false;
            return;
        }

        try {
            DB::transaction(function () {
                // Create the sale with shift_id and optional customer_id
                $sale = Sale::create([
                    'shop_id' => $this->shopId,
                    'customer_id' => $this->customerId, // NULL for walk-in sales
                    'staff_id' => auth()->id(),
                    'shift_id' => $this->activeShift->id,
                    'subtotal' => $this->subtotal,
                    'tax' => $this->tax,
                    'discount' => $this->discount,
                    'total' => $this->total,
                    'payment_method' => $this->paymentMethod,
                    'status' => Sale::STATUS_COMPLETED,
                    'notes' => $this->notes,
                ]);

                // Process each cart item using FIFO
                foreach ($this->cart as $categoryId => $item) {
                    $remainingQty = $item['quantity'];

                    // Get inventory ordered by FIFO (oldest batches first)
                    $inventories = Inventory::where('shop_id', $this->shopId)
                        ->where('egg_category_id', $categoryId)
                        ->where('available_stock', '>', 0)
                        ->fifo()
                        ->lockForUpdate()
                        ->get();

                    foreach ($inventories as $inventory) {
                        if ($remainingQty <= 0) {
                            break;
                        }

                        $deductQty = min($remainingQty, $inventory->available_stock);

                        // Create sale item for this batch
                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'batch_id' => $inventory->batch_id,
                            'egg_category_id' => $categoryId,
                            'quantity' => $deductQty,
                            'unit_price' => $item['price'],
                            'line_total' => $deductQty * $item['price'],
                        ]);

                        // Deduct from inventory
                        $inventory->available_stock -= $deductQty;
                        $inventory->save();

                        // Update batch current_quantity
                        if ($inventory->batch) {
                            $inventory->batch->decrement('current_quantity', $deductQty);
                        }

                        $remainingQty -= $deductQty;

                        // Check for low stock and broadcast
                        $category = EggCategory::find($categoryId);
                        $totalStock = Inventory::getTotalStockForCategory($this->shopId, $categoryId);
                        
                        event(new StockUpdated(
                            $this->shopId,
                            $categoryId,
                            $totalStock['available'],
                            $totalStock['reserved'],
                            'sold'
                        ));

                        if ($category && $totalStock['available'] <= $category->low_stock_threshold && $totalStock['available'] > 0) {
                            event(new LowStockDetected(
                                $this->shopId,
                                $category,
                                $totalStock['available'],
                                $category->low_stock_threshold
                            ));
                        }
                    }

                    if ($remainingQty > 0) {
                        throw new InsufficientStockException(
                            "Insufficient stock for {$item['name']}. Only " . ($item['quantity'] - $remainingQty) . " available."
                        );
                    }
                }

                // Fire sale completed event
                event(new SaleCompleted($sale));

                $this->lastSale = $sale;
            });

            // Clear cart, customer, and close modal
            $this->cart = [];
            $this->discount = 0;
            $this->notes = '';
            $this->clearCustomer();
            $this->saveCart();
            $this->showCheckoutModal = false;
            $this->showReceiptModal = true;

            session()->flash('success', 'Sale completed successfully!');

        } catch (InsufficientStockException $e) {
            session()->flash('error', $e->getMessage());
            $this->showCheckoutModal = false;
            // Refresh available categories
            unset($this->availableCategories);
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
            $this->showCheckoutModal = false;
        }
    }

    /**
     * Close receipt modal and start new sale.
     */
    public function newSale(): void
    {
        $this->showReceiptModal = false;
        $this->lastSale = null;
        $this->amountTendered = 0;
        $this->clearCustomer();
        unset($this->availableCategories);
    }

    /**
     * Handle stock updates from Echo.
     */
    #[On('echo:stock.{shopId},StockUpdated')]
    public function handleStockUpdate(): void
    {
        unset($this->availableCategories);
    }

    public function render()
    {
        return view('livewire.pos.point-of-sale');
    }
}
