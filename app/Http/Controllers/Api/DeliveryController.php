<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeliveryRequest;
use App\Http\Requests\DispatchDeliveryRequest;
use App\Http\Requests\ReceiveDeliveryRequest;
use App\Http\Resources\DeliveryResource;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\DeliveryDiscrepancy;
use App\Models\RestockRequest;
use App\Models\WastageLog;
use App\Services\InventoryService;
use App\Events\DeliveryDispatched;
use App\Events\DeliveryReceived;
use App\Events\DeliveryDiscrepancyReported;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of deliveries.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Delivery::class);

        $query = Delivery::with(['restockRequest.shop', 'restockRequest.eggCategory', 'dispatcher', 'receiver']);

        // Filter by shop for shop staff
        if ($request->user()->isShopStaff()) {
            $query->whereHas('restockRequest', function ($q) use ($request) {
                $q->where('shop_id', $request->user()->shop_id);
            });
        }

        // Optional filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('shop_id')) {
            $query->whereHas('restockRequest', function ($q) use ($request) {
                $q->where('shop_id', $request->shop_id);
            });
        }

        $deliveries = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return DeliveryResource::collection($deliveries)->response();
    }

    /**
     * Store a newly created delivery (create from restock request).
     */
    public function store(StoreDeliveryRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $restockRequest = RestockRequest::findOrFail($request->restock_request_id);

            $delivery = Delivery::create([
                'restock_request_id' => $request->restock_request_id,
                'shop_id' => $restockRequest->shop_id,
                'status' => Delivery::STATUS_DISPATCHED,
                'dispatched_by' => $request->user()->id,
                'dispatched_at' => now(),
                'notes' => $request->notes,
            ]);

            // Create delivery items
            foreach ($request->items as $item) {
                DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'batch_id' => $item['batch_id'],
                    'egg_category_id' => $item['egg_category_id'],
                    'qty_sent' => $item['quantity_sent'],
                ]);
            }

            // Update restock request status
            $restockRequest->markInTransit();

            event(new DeliveryDispatched($delivery));

            return response()->json([
                'message' => 'Delivery created and dispatched successfully.',
                'data' => new DeliveryResource($delivery->load(['items', 'restockRequest'])),
            ], 201);
        });
    }

    /**
     * Display the specified delivery.
     */
    public function show(Delivery $delivery): JsonResponse
    {
        $this->authorize('view', $delivery);

        $delivery->load([
            'restockRequest.shop',
            'restockRequest.eggCategory',
            'items.batch',
            'items.eggCategory',
            'discrepancies',
            'wastageLogs',
            'dispatcher',
            'receiver',
        ]);

        return response()->json(['data' => new DeliveryResource($delivery)]);
    }

    /**
     * Receive a delivery (Shop Staff confirmation).
     */
    public function receive(ReceiveDeliveryRequest $request, Delivery $delivery): JsonResponse
    {
        $this->authorize('receive', $delivery);

        return DB::transaction(function () use ($request, $delivery) {
            $hasDiscrepancy = false;
            $totalReceived = 0;
            $totalRejected = 0;

            foreach ($request->items as $itemData) {
                $item = DeliveryItem::findOrFail($itemData['delivery_item_id']);
                
                $qtyReceived = $itemData['quantity_received'];
                $qtyRejected = $itemData['quantity_rejected'] ?? 0;
                $qtyMissing = $item->qty_sent - $qtyReceived - $qtyRejected;

                $item->update([
                    'qty_received' => $qtyReceived,
                    'qty_rejected' => $qtyRejected,
                    'rejection_reason' => $itemData['rejection_reason'] ?? null,
                ]);

                // Add received stock to inventory
                if ($qtyReceived > 0) {
                    $this->inventoryService->addStock(
                        $delivery->shop_id,
                        $item->egg_category_id,
                        $item->batch_id,
                        $qtyReceived
                    );
                }

                // Create discrepancy if qty doesn't match
                if ($qtyMissing != 0) {
                    $hasDiscrepancy = true;
                    DeliveryDiscrepancy::create([
                        'delivery_id' => $delivery->id,
                        'delivery_item_id' => $item->id,
                        'qty_sent' => $item->qty_sent,
                        'qty_received' => $qtyReceived,
                        'qty_rejected' => $qtyRejected,
                        'qty_missing' => $qtyMissing,
                        'reported_by' => $request->user()->id,
                        'notes' => $request->notes ?? 'Auto-created from delivery confirmation',
                    ]);
                }

                // Log wastage for rejected items
                if ($qtyRejected > 0) {
                    WastageLog::createFromDeliveryRejection($item, $request->user()->id, $delivery->shop_id);
                }

                $totalReceived += $qtyReceived;
                $totalRejected += $qtyRejected;
            }

            // Update delivery status
            if ($hasDiscrepancy || $totalRejected > 0) {
                $delivery->markPartial($request->user()->id);
            } else {
                $delivery->confirmReceipt($request->user()->id);
            }

            // Update restock request
            $restockRequest = $delivery->restockRequest;
            $restockRequest->recordFulfillment($totalReceived);

            event(new DeliveryReceived($delivery));

            if ($hasDiscrepancy) {
                event(new DeliveryDiscrepancyReported($delivery));
            }

            return response()->json([
                'message' => 'Delivery received and processed successfully.',
                'data' => new DeliveryResource($delivery->fresh(['items', 'discrepancies', 'wastageLogs'])),
                'summary' => [
                    'total_received' => $totalReceived,
                    'total_rejected' => $totalRejected,
                    'has_discrepancy' => $hasDiscrepancy,
                ],
            ]);
        });
    }

    /**
     * Get in-transit deliveries for a shop.
     */
    public function inTransit(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Delivery::class);

        $query = Delivery::with(['restockRequest.eggCategory', 'items'])
            ->where('status', Delivery::STATUS_DISPATCHED);

        if ($request->user()->isShopStaff()) {
            $query->where('shop_id', $request->user()->shop_id);
        }

        $deliveries = $query->orderBy('dispatched_at')->get();

        return response()->json([
            'data' => DeliveryResource::collection($deliveries),
            'count' => $deliveries->count(),
        ]);
    }
}
