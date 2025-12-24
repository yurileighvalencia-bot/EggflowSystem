<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShopRequest;
use App\Http\Requests\UpdateShopRequest;
use App\Http\Resources\ShopResource;
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
        $this->authorize('viewAny', Shop::class);

        $query = Shop::with('farm')->withCount(['users', 'inventories']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        $query->orderBy('name');

        // Support ?all=true for dropdowns, otherwise paginate
        if ($request->boolean('all')) {
            return response()->json(['data' => ShopResource::collection($query->get())]);
        }

        return ShopResource::collection($query->paginate($request->integer('per_page', 15)))->response();
    }

    /**
     * Store a newly created shop.
     */
    public function store(StoreShopRequest $request): JsonResponse
    {
        $shop = Shop::create($request->validated());

        return response()->json([
            'message' => 'Shop created successfully.',
            'data' => new ShopResource($shop->load('farm')),
        ], 201);
    }

    /**
     * Display the specified shop.
     */
    public function show(Shop $shop): JsonResponse
    {
        $this->authorize('view', $shop);

        $shop->load(['farm', 'users']);
        $shop->loadCount(['users', 'inventories']);

        return response()->json(['data' => new ShopResource($shop)]);
    }

    /**
     * Update the specified shop.
     */
    public function update(UpdateShopRequest $request, Shop $shop): JsonResponse
    {
        $shop->update($request->validated());

        return response()->json([
            'message' => 'Shop updated successfully.',
            'data' => new ShopResource($shop->fresh(['farm'])),
        ]);
    }
}
