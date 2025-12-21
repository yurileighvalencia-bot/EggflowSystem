<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Reservation;
use App\Models\EggCategory;
use App\Services\InventoryService;
use App\Exceptions\InsufficientStockException;
use App\Events\SaleCompleted;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of sales.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Sale::class);

        $query = Sale::with(['shop', 'customer', 'staff', 'items.eggCategory']);

        // Filter based on user role
        if ($request->user()->isCustomer()) {
            $query->where('customer_id', $request->user()->id);
        } elseif ($request->user()->isShopStaff()) {
            $query->where('shop_id', $request->user()->shop_id);
        }

        // Optional filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->whereDate('sold_at', $request->date);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->betweenDates($request->from_date, $request->to_date);
        }

        $sales = $query->orderByDesc('sold_at')
            ->paginate($request->get('per_page', 15));

        return response()->json($sales);
    }

    /**
     * Store a newly created sale.
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $shopId = $request->shop_id;
            $subtotal = 0;

            // Check if this is a reservation fulfillment
            if ($request->has('reservation_id')) {
                return $this->fulfillReservation($request);
            }

            $sale = Sale::create([
                'shop_id' => $shopId,
                'customer_id' => $request->customer_id,
                'staff_id' => $request->user()->id,
                'payment_method' => $request->payment_method,
                'status' => Sale::STATUS_COMPLETED,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $itemData) {
                $categoryId = $itemData['egg_category_id'];
                $quantity = $itemData['quantity'];

                // Get unit price
                $unitPrice = $itemData['unit_price'] 
                    ?? EggCategory::find($categoryId)->default_price 
                    ?? 0;

                // Deduct stock using FIFO
                try {
                    $deductions = $this->inventoryService->deductStock($shopId, $categoryId, $quantity);
                } catch (InsufficientStockException $e) {
                    throw $e;
                }

                // Create sale items for each batch deduction
                foreach ($deductions as $deduction) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'batch_id' => $deduction['batch_id'],
                        'egg_category_id' => $categoryId,
                        'quantity' => $deduction['quantity'],
                        'unit_price' => $unitPrice,
                    ]);
                }

                $subtotal += $unitPrice * $quantity;
            }

            $sale->update([
                'subtotal' => $subtotal,
                'discount' => $request->discount_amount ?? 0,
                'total' => $subtotal - ($request->discount_amount ?? 0),
            ]);

            event(new SaleCompleted($sale));

            return response()->json([
                'message' => 'Sale completed successfully.',
                'data' => $sale->load(['shop', 'customer', 'staff', 'items.eggCategory', 'items.batch']),
            ], 201);
        });
    }

    /**
     * Fulfill a reservation as a sale.
     */
    protected function fulfillReservation(StoreSaleRequest $request): JsonResponse
    {
        $reservation = Reservation::with('items')->findOrFail($request->reservation_id);

        if (!$reservation->isActive()) {
            return response()->json([
                'error' => 'This reservation cannot be fulfilled.',
            ], 422);
        }

        $sale = Sale::create([
            'shop_id' => $reservation->shop_id,
            'customer_id' => $reservation->customer_id,
            'staff_id' => $request->user()->id,
            'reservation_id' => $reservation->id,
            'payment_method' => $request->payment_method,
            'subtotal' => $reservation->subtotal,
            'tax' => $reservation->tax,
            'total' => $reservation->total,
            'status' => Sale::STATUS_COMPLETED,
            'notes' => $request->notes,
        ]);

        foreach ($reservation->items as $item) {
            // Fulfill reserved stock
            $deductions = $this->inventoryService->fulfillReservedStock(
                $reservation->shop_id,
                $item->egg_category_id,
                $item->quantity
            );

            foreach ($deductions as $deduction) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'batch_id' => $deduction['batch_id'],
                    'egg_category_id' => $item->egg_category_id,
                    'quantity' => $deduction['quantity'],
                    'unit_price' => $item->unit_price,
                ]);
            }
        }

        $reservation->complete();

        event(new SaleCompleted($sale));

        return response()->json([
            'message' => 'Reservation fulfilled successfully.',
            'data' => $sale->load(['shop', 'customer', 'staff', 'items.eggCategory', 'reservation']),
        ], 201);
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale): JsonResponse
    {
        $this->authorize('view', $sale);

        return response()->json([
            'data' => $sale->load([
                'shop',
                'customer',
                'staff',
                'reservation',
                'items.eggCategory',
                'items.batch',
            ]),
        ]);
    }

    /**
     * Void a sale (Manager only).
     */
    public function void(Request $request, Sale $sale): JsonResponse
    {
        $this->authorize('void', $sale);

        $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        // Note: Stock is not automatically restored on void
        // This would need manual inventory adjustment if required
        $sale->void();
        $sale->update([
            'notes' => $sale->notes . "\nVoided: " . $request->reason,
        ]);

        return response()->json([
            'message' => 'Sale voided successfully.',
            'data' => $sale->fresh(),
        ]);
    }

    /**
     * Get today's sales summary.
     */
    public function todaySummary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Sale::class);

        $query = Sale::completed()->forDate(today());

        if ($request->user()->isShopStaff()) {
            $query->forShop($request->user()->shop_id);
        } elseif ($request->has('shop_id')) {
            $query->forShop($request->shop_id);
        }

        $summary = $query->selectRaw('
            COUNT(*) as transaction_count,
            SUM(subtotal) as total_subtotal,
            SUM(discount) as total_discount,
            SUM(total) as total_revenue,
            payment_method
        ')
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'data' => $summary,
            'total_transactions' => $summary->sum('transaction_count'),
            'total_revenue' => $summary->sum('total_revenue'),
        ]);
    }

    /**
     * Generate sale receipt data.
     */
    public function receipt(Sale $sale): JsonResponse
    {
        $this->authorize('view', $sale);

        return response()->json([
            'data' => [
                'sale' => $sale->load(['shop', 'customer', 'staff', 'items.eggCategory']),
                'receipt_number' => $sale->sale_code,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
