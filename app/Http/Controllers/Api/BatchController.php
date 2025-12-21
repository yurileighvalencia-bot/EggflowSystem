<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBatchRequest;
use App\Models\Batch;
use App\Models\EggCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json($batches);
    }

    /**
     * Store a newly created batch.
     */
    public function store(StoreBatchRequest $request): JsonResponse
    {
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
            'data' => $createdBatches,
        ], 201);
    }

    /**
     * Display the specified batch.
     */
    public function show(Batch $batch): JsonResponse
    {
        $this->authorize('view', $batch);

        return response()->json([
            'data' => $batch->load([
                'farm',
                'eggCategory',
                'dailyCollections.staff',
                'inventories.shop',
            ]),
        ]);
    }

    /**
     * Update the specified batch.
     */
    public function update(Request $request, Batch $batch): JsonResponse
    {
        $this->authorize('update', $batch);

        $validated = $request->validate([
            'expires_at' => ['nullable', 'date', 'after:collection_date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $batch->update($validated);

        return response()->json([
            'message' => 'Batch updated successfully.',
            'data' => $batch->fresh(['farm', 'eggCategory']),
        ]);
    }

    /**
     * Mark batch as expired.
     */
    public function expire(Request $request, Batch $batch): JsonResponse
    {
        $this->authorize('expire', $batch);

        $batch->update(['status' => 'expired']);

        // Log wastage for remaining quantity
        if ($batch->current_quantity > 0) {
            event(new \App\Events\BatchExpired($batch, $request->user()));
        }

        return response()->json([
            'message' => 'Batch marked as expired.',
            'data' => $batch->fresh(),
        ]);
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
            'data' => $batches,
            'total_expiring_quantity' => $batches->sum('current_quantity'),
        ]);
    }
}
