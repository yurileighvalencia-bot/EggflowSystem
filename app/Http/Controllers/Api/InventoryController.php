<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryResource;
use App\Http\Resources\EggCategoryResource;
use App\Models\Inventory;
use App\Models\EggCategory;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of inventory.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Inventory::class);

        $request->validate([
            'shop_id' => 'nullable|integer|exists:shops,id',
            'egg_category_id' => 'nullable|integer|exists:egg_categories,id',
            'low_stock' => 'nullable|boolean',
            'all' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Inventory::with(['shop', 'eggCategory', 'batch']);

        // Filter by shop for shop staff
        if ($request->user()->isShopStaff()) {
            $query->where('shop_id', $request->user()->shop_id);
        } elseif ($request->has('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        if ($request->has('egg_category_id')) {
            $query->where('egg_category_id', $request->egg_category_id);
        }

        // Filter for low stock items
        if ($request->boolean('low_stock')) {
            $query->whereHas('eggCategory', function ($q) {
                $q->whereColumn('inventories.available_stock', '<=', 'egg_categories.low_stock_threshold');
            });
        }

        $query->orderBy('shop_id')->orderBy('egg_category_id');

        // Support ?all=true for full inventory view, otherwise paginate
        if ($request->boolean('all')) {
            return response()->json(['data' => InventoryResource::collection($query->get())]);
        }

        return InventoryResource::collection($query->paginate($request->integer('per_page', 15)))->response();
    }

    /**
     * Get inventory summary by shop.
     */
    public function summary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Inventory::class);

        $shopId = $request->user()->isShopStaff() 
            ? $request->user()->shop_id 
            : $request->shop_id;

        if (!$shopId) {
            return response()->json(['message' => 'Shop ID is required.'], 400);
        }

        $categories = EggCategory::where('is_active', true)->get();

        $summary = $categories->map(function ($category) use ($shopId) {
            return array_merge(
                ['category' => $category],
                $this->inventoryService->getStockSummary($shopId, $category->id)
            );
        });

        return response()->json([
            'shop_id' => $shopId,
            'data' => $summary,
            'total_available' => $summary->sum('available'),
            'total_reserved' => $summary->sum('reserved'),
        ]);
    }

    /**
     * Get expiring inventory.
     */
    public function expiring(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Inventory::class);

        $shopId = $request->user()->isShopStaff()
            ? $request->user()->shop_id
            : $request->shop_id;

        if (!$shopId) {
            return response()->json(['message' => 'Shop ID is required.'], 400);
        }

        $days = $request->get('days', 3);
        $expiring = $this->inventoryService->getExpiringInventory($shopId, $days);

        return response()->json([
            'data' => $expiring,
            'total_expiring_quantity' => $expiring->sum('available_stock'),
        ]);
    }

    /**
     * Manual stock adjustment.
     */
    public function adjust(Request $request, Inventory $inventory): JsonResponse
    {
        $this->authorize('adjust', $inventory);

        $validated = $request->validate([
            'adjustment' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $oldStock = $inventory->available_stock;
        $newStock = $oldStock + $validated['adjustment'];

        if ($newStock < 0) {
            return response()->json([
                'message' => 'Adjustment would result in negative stock.',
            ], 422);
        }

        $inventory->available_stock = $newStock;
        $inventory->save();

        // Log the adjustment as wastage if reducing stock
        if ($validated['adjustment'] < 0) {
            $this->inventoryService->recordWastage(
                $inventory->shop_id,
                $inventory->egg_category_id,
                abs($validated['adjustment']),
                'inventory_adjustment',
                $validated['reason'],
                $request->user()->id,
                $inventory->batch_id
            );
        }

        return response()->json([
            'message' => 'Inventory adjusted successfully.',
            'data' => new InventoryResource($inventory->fresh(['shop', 'eggCategory', 'batch'])),
            'adjustment' => [
                'previous' => $oldStock,
                'change' => $validated['adjustment'],
                'current' => $newStock,
            ],
        ]);
    }

    /**
     * Get low stock alerts.
     */
    public function lowStockAlerts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Inventory::class);

        $query = Inventory::with(['shop', 'eggCategory', 'batch'])
            ->where('available_stock', '>', 0);

        if ($request->user()->isShopStaff()) {
            $query->where('shop_id', $request->user()->shop_id);
        }

        $inventory = $query->get();

        $lowStockItems = $inventory->filter(function ($item) {
            return $item->isBelowThreshold();
        })->values();

        return response()->json([
            'data' => InventoryResource::collection($lowStockItems),
            'total_items' => $lowStockItems->count(),
        ]);
    }
}
