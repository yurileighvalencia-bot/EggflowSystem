<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestockRequestRequest;
use App\Http\Requests\AcknowledgeRestockRequest;
use App\Http\Resources\RestockRequestResource;
use App\Models\RestockRequest;
use App\Events\RestockRequestCreated;
use App\Events\RestockRequestAcknowledged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestockRequestController extends Controller
{
    /**
     * Display a listing of restock requests.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RestockRequest::class);

        $query = RestockRequest::with(['shop', 'eggCategory', 'requester', 'acknowledger']);

        // Filter by shop for shop staff
        if ($request->user()->isShopStaff()) {
            $query->where('shop_id', $request->user()->shop_id);
        }

        // Optional filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        // Filter for active requests
        if ($request->boolean('active_only')) {
            $query->whereNotIn('status', [
                RestockRequest::STATUS_DELIVERED,
                RestockRequest::STATUS_CANCELLED,
            ]);
        }

        $requests = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return RestockRequestResource::collection($requests)->response();
    }

    /**
     * Store a newly created restock request.
     */
    public function store(StoreRestockRequestRequest $request): JsonResponse
    {
        // Check for existing active request
        if (RestockRequest::hasActiveRequest($request->shop_id, $request->egg_category_id)) {
            return response()->json([
                'message' => 'An active restock request already exists for this category.',
            ], 422);
        }

        return DB::transaction(function () use ($request) {
            $restockRequest = RestockRequest::create([
                'shop_id' => $request->shop_id,
                'egg_category_id' => $request->egg_category_id,
                'quantity_requested' => $request->quantity_requested,
                'quantity_remaining' => $request->quantity_requested,
                'status' => RestockRequest::STATUS_PENDING,
                'requested_by' => $request->user()->id,
                'notes' => $request->notes,
            ]);

            event(new RestockRequestCreated($restockRequest));

            return response()->json([
                'message' => 'Restock request created successfully.',
                'data' => new RestockRequestResource($restockRequest->load(['shop', 'eggCategory'])),
            ], 201);
        });
    }

    /**
     * Display the specified restock request.
     */
    public function show(RestockRequest $restockRequest): JsonResponse
    {
        $this->authorize('view', $restockRequest);

        $restockRequest->load([
            'shop',
            'eggCategory',
            'requester',
            'acknowledger',
            'deliveries.items',
        ]);

        return response()->json(['data' => new RestockRequestResource($restockRequest)]);
    }

    /**
     * Acknowledge a restock request (Farm Staff).
     */
    public function acknowledge(AcknowledgeRestockRequest $request, RestockRequest $restockRequest): JsonResponse
    {
        $this->authorize('acknowledge', $restockRequest);

        $restockRequest->acknowledge($request->user()->id);

        if ($request->has('notes')) {
            $restockRequest->update(['notes' => $restockRequest->notes . "\n" . $request->notes]);
        }

        event(new RestockRequestAcknowledged($restockRequest));

        return response()->json([
            'message' => 'Restock request acknowledged successfully.',
            'data' => new RestockRequestResource($restockRequest->fresh(['shop', 'eggCategory', 'acknowledger'])),
        ]);
    }

    /**
     * Cancel a restock request.
     */
    public function cancel(Request $request, RestockRequest $restockRequest): JsonResponse
    {
        $this->authorize('cancel', $restockRequest);

        $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $restockRequest->cancel();
        $restockRequest->update([
            'notes' => $restockRequest->notes . "\nCancelled: " . $request->reason,
        ]);

        return response()->json([
            'message' => 'Restock request cancelled successfully.',
            'data' => new RestockRequestResource($restockRequest->fresh()),
        ]);
    }

    /**
     * Get pending requests count (for dashboard).
     */
    public function pendingCount(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RestockRequest::class);

        $query = RestockRequest::where('status', RestockRequest::STATUS_PENDING);

        if ($request->user()->isShopStaff()) {
            $query->where('shop_id', $request->user()->shop_id);
        }

        return response()->json([
            'pending_count' => $query->count(),
        ]);
    }
}
