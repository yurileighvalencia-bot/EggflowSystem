<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBatchRequest;
use App\Http\Requests\UpdateBatchRequest;
use App\Http\Resources\BatchResource;
use App\Models\Batch;
use App\Models\EggCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchController extends Controller
{
    /**
     * Display a listing of batches.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Batch::class);

        $query = Batch::with(['farm', 'eggCategory']);

        // Filter by farm for farm staff
        if ($request->user()->isFarmStaff()) {
            $query->where('farm_id', $request->user()->farm_id);
        }

        // Optional filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('egg_category_id')) {
            $query->where('egg_category_id', $request->egg_category_id);
        }

        if ($request->has('from_date')) {
            $query->whereDate('collection_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('collection_date', '<=', $request->to_date);
        }

        // Filter for expiring soon batches
        if ($request->boolean('expiring_soon')) {
            $query->where('status', 'active')
                ->whereBetween('expires_at', [now(), now()->addDays(3)]);
        }

        $batches = $query->orderByDesc('collection_date')
            ->paginate($request->get('per_page', 15));

        return BatchResource::collection($batches)->response();
    }

    /**
     * Store a newly created batch.
     */
    public function store(StoreBatchRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $categories = EggCategory::where('is_active', true)->get();

            $createdBatches = [];

            foreach ($categories as $category) {
                $batch = Batch::create([
                    'farm_id' => $request->farm_id,
                    'egg_category_id' => $category->id,
                    'collection_date' => $request->collection_date,
                    'expires_at' => $request->expires_at,
                    'initial_quantity' => 0,
                    'current_quantity' => 0,
                    'status' => 'active',
                    'notes' => $request->notes,
                ]);

                $createdBatches[] = $batch->load(['farm', 'eggCategory']);
            }

            return response()->json([
                'message' => 'Batches created successfully for all active egg categories.',
                'data' => BatchResource::collection($createdBatches),
            ], 201);
        });
    }

    /**
     * Display the specified batch.
     */
    public function show(Batch $batch): JsonResponse
    {
        $this->authorize('view', $batch);

        $batch->load([
            'farm',
            'eggCategory',
            'dailyCollections.staff',
            'inventories.shop',
        ]);

        return response()->json(['data' => new BatchResource($batch)]);
    }

    /**
     * Update the specified batch.
     */
    public function update(UpdateBatchRequest $request, Batch $batch): JsonResponse
    {
        $batch->update($request->validated());

        return response()->json([
            'message' => 'Batch updated successfully.',
            'data' => new BatchResource($batch->fresh(['farm', 'eggCategory'])),
        ]);
    }

    /**
     * Mark batch as expired.
     */
    public function expire(Request $request, Batch $batch): JsonResponse
    {
        $this->authorize('expire', $batch);

        return DB::transaction(function () use ($request, $batch) {
            $wastedQuantity = $batch->current_quantity;
            
            $batch->update(['status' => 'expired']);

            // Fire event for notifications (wastage is handled by CheckBatchExpiry command)
            if ($wastedQuantity > 0) {
                event(new \App\Events\BatchExpired($batch, $wastedQuantity, $request->user()));
            }

            return response()->json([
                'message' => 'Batch marked as expired.',
                'data' => new BatchResource($batch->fresh()),
            ]);
        });
    }

    /**
     * Get batches expiring soon.
     */
    public function expiringSoon(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Batch::class);

        $days = $request->get('days', 3);

        $query = Batch::with(['farm', 'eggCategory'])
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);

        if ($request->user()->isFarmStaff()) {
            $query->where('farm_id', $request->user()->farm_id);
        }

        $batches = $query->orderBy('expires_at')->get();

        return response()->json([
            'data' => BatchResource::collection($batches),
            'total_expiring_quantity' => $batches->sum('current_quantity'),
        ]);
    }
}
