<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDailyCollectionRequest;
use App\Http\Requests\UpdateDailyCollectionRequest;
use App\Http\Resources\DailyCollectionResource;
use App\Models\DailyCollection;
use App\Models\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyCollectionController extends Controller
{
    /**
     * Display a listing of daily collections.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DailyCollection::class);

        $request->validate([
            'date' => 'nullable|date',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'egg_category_id' => 'nullable|integer|exists:egg_categories,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = DailyCollection::with(['staff', 'batch', 'eggCategory', 'farm']);

        // Filter by farm for farm staff
        if ($request->user()->isFarmStaff()) {
            $query->where('farm_id', $request->user()->farm_id);
        }

        // Optional filters
        if ($request->has('date')) {
            $query->whereDate('collection_date', $request->date);
        }

        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->has('egg_category_id')) {
            $query->where('egg_category_id', $request->egg_category_id);
        }

        $collections = $query->orderByDesc('collection_date')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return DailyCollectionResource::collection($collections)->response();
    }

    /**
     * Store a newly created daily collection.
     */
    public function store(StoreDailyCollectionRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $batch = Batch::findOrFail($request->batch_id);

            $collection = DailyCollection::create([
                'staff_id' => $request->user()->id,
                'batch_id' => $request->batch_id,
                'egg_category_id' => $request->egg_category_id,
                'farm_id' => $batch->farm_id,
                'quantity' => $request->quantity,
                'collection_date' => $batch->collection_date,
                'collection_time' => $request->collection_time ?? now()->format('H:i'),
                'notes' => $request->notes,
            ]);

            // Update batch quantity
            $batch->current_quantity += $request->quantity;
            
            // Update initial_quantity to track total collected eggs.
            // This becomes immutable once deliveries start (inventories exist).
            // Critical for yield/loss calculations.
            $hasInventory = $batch->inventories()->exists();
            if (!$hasInventory) {
                $batch->initial_quantity += $request->quantity;
            }
            
            $batch->save();

            return response()->json([
                'message' => 'Daily collection recorded successfully.',
                'data' => new DailyCollectionResource($collection->load(['staff', 'batch', 'eggCategory'])),
            ], 201);
        });
    }

    /**
     * Display the specified daily collection.
     */
    public function show(DailyCollection $collection): JsonResponse
    {
        $this->authorize('view', $collection);

        $collection->load(['staff', 'batch', 'eggCategory', 'farm', 'revisions.changedByUser']);

        return response()->json(['data' => new DailyCollectionResource($collection)]);
    }

    /**
     * Update the specified daily collection (with revision tracking).
     */
    public function update(UpdateDailyCollectionRequest $request, DailyCollection $collection): JsonResponse
    {
        $this->authorize('update', $collection);

        return DB::transaction(function () use ($request, $collection) {
            $oldQuantity = $collection->quantity;
            $newValues = $request->only(['quantity', 'collection_time', 'notes']);

            // Create revision record
            $collection->createRevision(
                $newValues,
                $request->revision_reason,
                $request->user()->id
            );

            // Update the collection
            $collection->update($newValues);

            // Adjust batch quantity if changed
            if (isset($newValues['quantity']) && $newValues['quantity'] !== $oldQuantity) {
                $batch = $collection->batch;
                $difference = $newValues['quantity'] - $oldQuantity;
                $batch->current_quantity += $difference;
                $batch->save();
            }

            return response()->json([
                'message' => 'Daily collection updated successfully.',
                'data' => new DailyCollectionResource($collection->fresh(['staff', 'batch', 'eggCategory', 'revisions'])),
            ]);
        });
    }

    /**
     * Verify a daily collection.
     */
    public function verify(Request $request, DailyCollection $collection): JsonResponse
    {
        $this->authorize('verify', $collection);

        $collection->update([
            'is_verified' => true,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Daily collection verified successfully.',
            'data' => new DailyCollectionResource($collection->fresh(['staff', 'verifier'])),
        ]);
    }

    /**
     * Get summary statistics for collections.
     */
    public function summary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DailyCollection::class);

        $query = DailyCollection::query();

        if ($request->user()->isFarmStaff()) {
            $query->where('farm_id', $request->user()->farm_id);
        }

        if ($request->has('date')) {
            $query->whereDate('collection_date', $request->date);
        } else {
            $query->whereDate('collection_date', today());
        }

        $summary = $query->selectRaw('
            egg_category_id,
            SUM(quantity) as total_quantity,
            COUNT(*) as collection_count
        ')
            ->groupBy('egg_category_id')
            ->with('eggCategory')
            ->get();

        return response()->json([
            'data' => $summary,
            'total_eggs' => $summary->sum('total_quantity'),
            'total_collections' => $summary->sum('collection_count'),
        ]);
    }
}
