<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFarmRequest;
use App\Http\Requests\UpdateFarmRequest;
use App\Http\Resources\FarmResource;
use App\Models\Farm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmController extends Controller
{
    /**
     * Display a listing of farms.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Farm::class);

        $query = Farm::withCount(['users', 'shops', 'batches'])
            ->orderBy('name');

        // Support ?all=true for dropdowns, otherwise paginate
        if ($request->boolean('all')) {
            return response()->json(['data' => FarmResource::collection($query->get())]);
        }

        return FarmResource::collection($query->paginate($request->integer('per_page', 15)))->response();
    }

    /**
     * Store a newly created farm.
     */
    public function store(StoreFarmRequest $request): JsonResponse
    {
        $farm = Farm::create($request->validated());

        return response()->json([
            'message' => 'Farm created successfully.',
            'data' => new FarmResource($farm),
        ], 201);
    }

    /**
     * Display the specified farm.
     */
    public function show(Farm $farm): JsonResponse
    {
        $this->authorize('view', $farm);

        $farm->load(['users', 'batches' => function ($q) {
            $q->where('status', 'active')->latest('collection_date')->limit(10);
        }]);
        $farm->loadCount(['users', 'shops', 'batches']);

        return response()->json(['data' => new FarmResource($farm)]);
    }

    /**
     * Update the specified farm.
     */
    public function update(UpdateFarmRequest $request, Farm $farm): JsonResponse
    {
        $farm->update($request->validated());

        return response()->json([
            'message' => 'Farm updated successfully.',
            'data' => new FarmResource($farm->fresh()),
        ]);
    }
}
