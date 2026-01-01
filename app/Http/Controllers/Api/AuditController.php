<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    /**
     * Display a listing of audit logs with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Audit::class);

        $request->validate([
            'auditable_type' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id',
            'event' => 'nullable|string|in:created,updated,deleted,restored',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Audit::with(['user', 'auditable'])
            ->orderByDesc('created_at');

        // Filter by model type
        if ($request->filled('auditable_type')) {
            $modelClass = $this->resolveModelClass($request->auditable_type);
            if ($modelClass) {
                $query->where('auditable_type', $modelClass);
            }
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by event type
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $audits = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $audits->items(),
            'meta' => [
                'current_page' => $audits->currentPage(),
                'last_page' => $audits->lastPage(),
                'per_page' => $audits->perPage(),
                'total' => $audits->total(),
            ],
        ]);
    }

    /**
     * Get audit logs for a specific model instance.
     */
    public function show(Request $request, string $model, int $id): JsonResponse
    {
        $modelClass = $this->resolveModelClass($model);

        if (!$modelClass) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid model type.',
            ], 400);
        }

        $audits = Audit::with(['user'])
            ->where('auditable_type', $modelClass)
            ->where('auditable_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $audits,
        ]);
    }

    /**
     * Get available auditable models.
     */
    public function models(): JsonResponse
    {
        $models = [
            'batch' => 'App\\Models\\Batch',
            'daily_collection' => 'App\\Models\\DailyCollection',
            'delivery' => 'App\\Models\\Delivery',
            'delivery_discrepancy' => 'App\\Models\\DeliveryDiscrepancy',
            'egg_category' => 'App\\Models\\EggCategory',
            'farm' => 'App\\Models\\Farm',
            'inventory' => 'App\\Models\\Inventory',
            'reservation' => 'App\\Models\\Reservation',
            'restock_request' => 'App\\Models\\RestockRequest',
            'sale' => 'App\\Models\\Sale',
            'shift' => 'App\\Models\\Shift',
            'shift_adjustment' => 'App\\Models\\ShiftAdjustment',
            'shop' => 'App\\Models\\Shop',
            'user' => 'App\\Models\\User',
            'wastage_log' => 'App\\Models\\WastageLog',
        ];

        return response()->json([
            'success' => true,
            'data' => array_keys($models),
        ]);
    }

    /**
     * Get audit event types.
     */
    public function events(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['created', 'updated', 'deleted', 'restored'],
        ]);
    }

    /**
     * Get recent audit activity for dashboard.
     */
    public function recent(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $audits = Audit::with(['user', 'auditable'])
            ->orderByDesc('created_at')
            ->limit($request->get('limit', 10))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $audits->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'event' => $audit->event,
                    'auditable_type' => class_basename($audit->auditable_type),
                    'auditable_id' => $audit->auditable_id,
                    'user' => $audit->user ? [
                        'id' => $audit->user->id,
                        'name' => $audit->user->name,
                    ] : null,
                    'old_values' => $audit->old_values,
                    'new_values' => $audit->new_values,
                    'url' => $audit->url,
                    'ip_address' => $audit->ip_address,
                    'created_at' => $audit->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Resolve short model name to full class name.
     */
    protected function resolveModelClass(string $model): ?string
    {
        $models = [
            'batch' => 'App\\Models\\Batch',
            'daily_collection' => 'App\\Models\\DailyCollection',
            'daily-collection' => 'App\\Models\\DailyCollection',
            'dailycollection' => 'App\\Models\\DailyCollection',
            'delivery' => 'App\\Models\\Delivery',
            'delivery_discrepancy' => 'App\\Models\\DeliveryDiscrepancy',
            'delivery-discrepancy' => 'App\\Models\\DeliveryDiscrepancy',
            'deliverydiscrepancy' => 'App\\Models\\DeliveryDiscrepancy',
            'egg_category' => 'App\\Models\\EggCategory',
            'egg-category' => 'App\\Models\\EggCategory',
            'eggcategory' => 'App\\Models\\EggCategory',
            'farm' => 'App\\Models\\Farm',
            'inventory' => 'App\\Models\\Inventory',
            'reservation' => 'App\\Models\\Reservation',
            'restock_request' => 'App\\Models\\RestockRequest',
            'restock-request' => 'App\\Models\\RestockRequest',
            'restockrequest' => 'App\\Models\\RestockRequest',
            'sale' => 'App\\Models\\Sale',
            'shift' => 'App\\Models\\Shift',
            'shift_adjustment' => 'App\\Models\\ShiftAdjustment',
            'shift-adjustment' => 'App\\Models\\ShiftAdjustment',
            'shiftadjustment' => 'App\\Models\\ShiftAdjustment',
            'shop' => 'App\\Models\\Shop',
            'user' => 'App\\Models\\User',
            'wastage_log' => 'App\\Models\\WastageLog',
            'wastage-log' => 'App\\Models\\WastageLog',
            'wastagelog' => 'App\\Models\\WastageLog',
        ];

        $key = strtolower($model);
        return $models[$key] ?? null;
    }
}
