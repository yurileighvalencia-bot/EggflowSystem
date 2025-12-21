<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * Display a listing of shops.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Shop::with('farm');

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        $shops = $query->orderBy('name')->get();

        return response()->json(['data' => $shops]);
    }

    /**
     * Store a newly created shop.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => ['nullable', 'exists:farms,id'],
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $shop = Shop::create($validated);

        return response()->json([
            'message' => 'Shop created successfully.',
            'data' => $shop->load('farm'),
        ], 201);
    }

    /**
     * Display the specified shop.
     */
    public function show(Shop $shop): JsonResponse
    {
        return response()->json([
            'data' => $shop->load(['farm', 'users']),
        ]);
    }

    /**
     * Update the specified shop.
     */
    public function update(Request $request, Shop $shop): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => ['nullable', 'exists:farms,id'],
            'name' => ['sometimes', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $shop->update($validated);

        return response()->json([
            'message' => 'Shop updated successfully.',
            'data' => $shop->fresh(['farm']),
        ]);
    }
}
