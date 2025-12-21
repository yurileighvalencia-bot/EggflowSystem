<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportDiscrepancyRequest;
use App\Http\Requests\InvestigateDiscrepancyRequest;
use App\Models\DeliveryDiscrepancy;
use App\Events\DiscrepancyInvestigated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryDiscrepancyController extends Controller
{
    /**
     * Display a listing of discrepancies.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DeliveryDiscrepancy::with([
            'delivery.restockRequest.shop',
            'deliveryItem.eggCategory',
            'reporter',
            'investigator',
        ]);

        // Filter by shop for shop staff
        if ($request->user()->isShopStaff()) {
            $query->whereHas('delivery', function ($q) use ($request) {
                $q->where('shop_id', $request->user()->shop_id);
            });
        }

        // Optional filters
        if ($request->has('status')) {
            if ($request->status === 'pending') {
                $query->whereNull('resolution');
            } else {
                $query->where('resolution', $request->status);
            }
        }

        if ($request->has('delivery_id')) {
            $query->where('delivery_id', $request->delivery_id);
        }

        $discrepancies = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json($discrepancies);
    }

    /**
     * Display the specified discrepancy.
     */
    public function show(DeliveryDiscrepancy $discrepancy): JsonResponse
    {
        return response()->json([
            'data' => $discrepancy->load([
                'delivery.restockRequest.shop',
                'delivery.restockRequest.eggCategory',
                'deliveryItem.batch',
                'reporter',
                'investigator',
            ]),
        ]);
    }

    /**
     * Report a new discrepancy manually.
     */
    public function store(ReportDiscrepancyRequest $request): JsonResponse
    {
        $qtyMissing = $request->qty_sent - $request->qty_received - ($request->qty_rejected ?? 0);

        $discrepancy = DeliveryDiscrepancy::create([
            'delivery_id' => $request->delivery_id,
            'delivery_item_id' => $request->delivery_item_id,
            'qty_sent' => $request->qty_sent,
            'qty_received' => $request->qty_received,
            'qty_rejected' => $request->qty_rejected ?? 0,
            'qty_missing' => $qtyMissing,
            'reported_by' => $request->user()->id,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Discrepancy reported successfully.',
            'data' => $discrepancy->load(['delivery', 'deliveryItem', 'reporter']),
        ], 201);
    }

    /**
     * Investigate a discrepancy (Manager only).
     */
    public function investigate(InvestigateDiscrepancyRequest $request, DeliveryDiscrepancy $discrepancy): JsonResponse
    {
        $discrepancy->update([
            'resolution' => $request->resolution,
            'investigated_by' => $request->user()->id,
            'notes' => $discrepancy->notes . "\n\nInvestigation: " . $request->resolution_notes,
        ]);

        event(new DiscrepancyInvestigated($discrepancy));

        return response()->json([
            'message' => 'Discrepancy investigation recorded.',
            'data' => $discrepancy->fresh(['delivery', 'investigator']),
        ]);
    }

    /**
     * Get pending discrepancies count.
     */
    public function pendingCount(Request $request): JsonResponse
    {
        $query = DeliveryDiscrepancy::whereNull('resolution');

        if ($request->user()->isShopStaff()) {
            $query->whereHas('delivery', function ($q) use ($request) {
                $q->where('shop_id', $request->user()->shop_id);
            });
        }

        return response()->json([
            'pending_count' => $query->count(),
        ]);
    }
}
