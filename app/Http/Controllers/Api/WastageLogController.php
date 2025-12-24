<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWastageLogRequest;
use App\Http\Resources\WastageLogResource;
use App\Models\WastageLog;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WastageLogController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of wastage logs.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WastageLog::class);

        $query = WastageLog::with(['shop', 'batch', 'delivery', 'eggCategory', 'logger']);

        // Filter by shop for shop staff
        if ($request->user()->isShopStaff()) {
            $query->where('shop_id', $request->user()->shop_id);
        }

        // Optional filters
        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        if ($request->has('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        if ($request->has('from_date')) {
            $query->whereDate('logged_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('logged_at', '<=', $request->to_date);
        }

        $logs = $query->orderByDesc('logged_at')
            ->paginate($request->get('per_page', 15));

        return WastageLogResource::collection($logs)->response();
    }

    /**
     * Store a newly created wastage log.
     */
    public function store(StoreWastageLogRequest $request): JsonResponse
    {
        $log = $this->inventoryService->recordWastage(
            $request->shop_id ?? $request->user()->shop_id,
            $request->egg_category_id,
            $request->quantity,
            $request->source,
            $request->reason,
            $request->user()->id,
            $request->batch_id,
            $request->delivery_id
        );

        return response()->json([
            'message' => 'Wastage logged successfully.',
            'data' => new WastageLogResource($log->load(['shop', 'batch', 'eggCategory', 'logger'])),
        ], 201);
    }

    /**
     * Display the specified wastage log.
     */
    public function show(WastageLog $wastageLog): JsonResponse
    {
        $this->authorize('view', $wastageLog);

        $wastageLog->load([
            'shop',
            'batch',
            'delivery',
            'eggCategory',
            'logger',
        ]);

        return response()->json(['data' => new WastageLogResource($wastageLog)]);
    }

    /**
     * Get wastage summary/statistics.
     */
    public function summary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WastageLog::class);

        $query = WastageLog::query();

        if ($request->user()->isShopStaff()) {
            $query->where('shop_id', $request->user()->shop_id);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('logged_at', [$request->from_date, $request->to_date]);
        } else {
            // Default to current month
            $query->whereMonth('logged_at', now()->month)
                ->whereYear('logged_at', now()->year);
        }

        $bySource = (clone $query)->selectRaw('
            source,
            SUM(quantity) as total_quantity,
            COUNT(*) as log_count
        ')
            ->groupBy('source')
            ->get();

        $byCategory = (clone $query)->selectRaw('
            egg_category_id,
            SUM(quantity) as total_quantity
        ')
            ->groupBy('egg_category_id')
            ->with('eggCategory')
            ->get();

        return response()->json([
            'by_source' => $bySource,
            'by_category' => $byCategory,
            'total_wastage' => $bySource->sum('total_quantity'),
            'total_logs' => $bySource->sum('log_count'),
        ]);
    }

    /**
     * Get wastage sources list.
     */
    public function sources(): JsonResponse
    {
        return response()->json([
            'sources' => [
                WastageLog::SOURCE_SHOP_SPOILAGE => 'Shop Spoilage',
                WastageLog::SOURCE_DELIVERY_REJECTION => 'Delivery Rejection',
                WastageLog::SOURCE_BATCH_EXPIRED => 'Batch Expired',
                WastageLog::SOURCE_INVENTORY_ADJUSTMENT => 'Inventory Adjustment',
                WastageLog::SOURCE_OTHER => 'Other',
            ],
        ]);
    }
}
