<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Services\InventoryService;
use App\Events\ReservationCreated;
use App\Events\ReservationCancelled;
use App\Events\ReservationExpired;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of reservations.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Reservation::class);

        $query = Reservation::with(['shop', 'customer', 'items.eggCategory']);

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

        if ($request->has('pickup_date')) {
            $query->whereDate('pickup_date', $request->pickup_date);
        }

        if ($request->boolean('active_only')) {
            $query->active();
        }

        $reservations = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json($reservations);
    }

    /**
     * Store a newly created reservation.
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $reservation = Reservation::create([
                'shop_id' => $request->shop_id,
                'customer_id' => $request->user()->id,
                'status' => Reservation::STATUS_PENDING,
                'pickup_date' => $request->pickup_date,
                'pickup_time' => $request->pickup_time,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $itemData) {
                // Reserve stock
                $this->inventoryService->reserveStock(
                    $request->shop_id,
                    $itemData['egg_category_id'],
                    $itemData['quantity']
                );

                ReservationItem::create([
                    'reservation_id' => $reservation->id,
                    'egg_category_id' => $itemData['egg_category_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'] ?? null,
                ]);
            }

            $reservation->calculateTotals();
            $reservation->save();

            event(new ReservationCreated($reservation));

            return response()->json([
                'message' => 'Reservation created successfully.',
                'data' => $reservation->load(['shop', 'items.eggCategory']),
            ], 201);
        });
    }

    /**
     * Display the specified reservation.
     */
    public function show(Reservation $reservation): JsonResponse
    {
        $this->authorize('view', $reservation);

        return response()->json([
            'data' => $reservation->load([
                'shop',
                'customer',
                'items.eggCategory',
                'items.batch',
                'sale',
            ]),
        ]);
    }

    /**
     * Update the specified reservation.
     */
    public function update(UpdateReservationRequest $request, Reservation $reservation): JsonResponse
    {
        $this->authorize('update', $reservation);

        return DB::transaction(function () use ($request, $reservation) {
            // Handle item updates if provided
            if ($request->has('items')) {
                // Release current reserved stock
                foreach ($reservation->items as $item) {
                    $this->inventoryService->releaseReservedStock(
                        $reservation->shop_id,
                        $item->egg_category_id,
                        $item->quantity
                    );
                }

                // Delete existing items
                $reservation->items()->delete();

                // Create new items and reserve stock
                foreach ($request->items as $itemData) {
                    $this->inventoryService->reserveStock(
                        $reservation->shop_id,
                        $itemData['egg_category_id'],
                        $itemData['quantity']
                    );

                    ReservationItem::create([
                        'reservation_id' => $reservation->id,
                        'egg_category_id' => $itemData['egg_category_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'] ?? null,
                    ]);
                }
            }

            $reservation->update($request->only(['pickup_date', 'pickup_time', 'notes']));
            $reservation->calculateTotals();
            $reservation->save();

            return response()->json([
                'message' => 'Reservation updated successfully.',
                'data' => $reservation->fresh(['shop', 'items.eggCategory']),
            ]);
        });
    }

    /**
     * Confirm a reservation (Shop Staff).
     */
    public function confirm(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorize('confirm', $reservation);

        $reservation->confirm();

        return response()->json([
            'message' => 'Reservation confirmed successfully.',
            'data' => $reservation->fresh(),
        ]);
    }

    /**
     * Mark reservation as ready for pickup.
     */
    public function markReady(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorize('confirm', $reservation);

        $reservation->markReady();

        return response()->json([
            'message' => 'Reservation marked as ready for pickup.',
            'data' => $reservation->fresh(),
        ]);
    }

    /**
     * Cancel a reservation.
     */
    public function cancel(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorize('cancel', $reservation);

        return DB::transaction(function () use ($request, $reservation) {
            // Release reserved stock
            foreach ($reservation->items as $item) {
                $this->inventoryService->releaseReservedStock(
                    $reservation->shop_id,
                    $item->egg_category_id,
                    $item->quantity
                );
            }

            $reservation->cancel();

            event(new ReservationCancelled($reservation));

            return response()->json([
                'message' => 'Reservation cancelled successfully.',
                'data' => $reservation->fresh(),
            ]);
        });
    }

    /**
     * Get reservations for today's pickup.
     */
    public function todayPickups(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Reservation::class);

        $query = Reservation::with(['customer', 'items.eggCategory'])
            ->whereDate('pickup_date', today())
            ->whereIn('status', [
                Reservation::STATUS_CONFIRMED,
                Reservation::STATUS_READY,
            ]);

        if ($request->user()->isShopStaff()) {
            $query->where('shop_id', $request->user()->shop_id);
        }

        $reservations = $query->orderBy('pickup_time')->get();

        return response()->json([
            'data' => $reservations,
            'count' => $reservations->count(),
        ]);
    }

    /**
     * Expire old reservations (called by scheduler).
     */
    public function expireOld(): JsonResponse
    {
        $expired = Reservation::active()
            ->where('expires_at', '<', now())
            ->get();

        DB::transaction(function () use ($expired) {
            foreach ($expired as $reservation) {
                // Release reserved stock
                foreach ($reservation->items as $item) {
                    $this->inventoryService->releaseReservedStock(
                        $reservation->shop_id,
                        $item->egg_category_id,
                        $item->quantity
                    );
                }

                $reservation->expire();
                event(new ReservationExpired($reservation));
            }
        });

        return response()->json([
            'message' => 'Expired reservations processed.',
            'count' => $expired->count(),
        ]);
    }
}
