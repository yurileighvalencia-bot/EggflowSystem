<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $farms = Farm::orderBy('name')->get();

        return response()->json(['data' => $farms]);
    }

    /**
     * Store a newly created farm.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:farms,name'],
            'address' => ['nullable', 'string', 'max:500'],
            'contact' => ['nullable', 'string', 'max:100'],
        ]);

        $farm = Farm::create($validated);

        return response()->json([
            'message' => 'Farm created successfully.',
            'data' => $farm,
        ], 201);
    }

    /**
     * Display the specified farm.
     */
    public function show(Farm $farm): JsonResponse
    {
        return response()->json([
            'data' => $farm->load(['users', 'batches' => function ($q) {
                $q->where('status', 'active')->latest('collection_date')->limit(10);
            }]),
        ]);
    }

    /**
     * Update the specified farm.
     */
    public function update(Request $request, Farm $farm): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100', 'unique:farms,name,' . $farm->id],
            'address' => ['nullable', 'string', 'max:500'],
            'contact' => ['nullable', 'string', 'max:100'],
        ]);

        $farm->update($validated);

        return response()->json([
            'message' => 'Farm updated successfully.',
            'data' => $farm->fresh(),
        ]);
    }
}
