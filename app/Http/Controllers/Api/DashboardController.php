<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Batch;
use App\Models\Inventory;
use App\Models\RestockRequest;
use App\Models\Reservation;
use App\Models\WastageLog;
use App\Models\DeliveryDiscrepancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard data based on user role.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isManager()) {
            return $this->managerDashboard($request);
        }

        if ($user->isFarmStaff()) {
            return $this->farmStaffDashboard($request);
        }

        if ($user->isShopStaff()) {
            return $this->shopStaffDashboard($request);
        }

        if ($user->isCustomer()) {
            return $this->customerDashboard($request);
        }

        return response()->json(['message' => 'Invalid role.'], 403);
    }

    /**
     * Manager dashboard - full overview.
     */
    protected function managerDashboard(Request $request): JsonResponse
    {
        $today = today();

        // Today's sales
        $todaySales = Sale::completed()
            ->whereDate('sold_at', $today)
            ->selectRaw('COUNT(*) as count, SUM(total) as revenue')
            ->first();

        // Pending restock requests
        $pendingRestocks = RestockRequest::where('status', RestockRequest::STATUS_PENDING)->count();

        // Pending discrepancies
        $pendingDiscrepancies = DeliveryDiscrepancy::whereNull('resolution')->count();

        // Low stock alerts count
        $lowStockCount = Inventory::where('available_stock', '>', 0)
            ->get()
            ->filter(fn($i) => $i->isBelowThreshold())
            ->count();

        // Expiring batches (within 3 days)
        $expiringBatches = Batch::where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->whereBetween('expires_at', [now(), now()->addDays(3)])
            ->count();

        // This month's wastage
        $monthlyWastage = WastageLog::whereMonth('logged_at', now()->month)
            ->whereYear('logged_at', now()->year)
            ->sum('quantity');

        // Active reservations
        $activeReservations = Reservation::active()->count();

        return response()->json([
            'role' => 'manager',
            'data' => [
                'today_sales' => [
                    'count' => $todaySales->count ?? 0,
                    'revenue' => $todaySales->revenue ?? 0,
                ],
                'pending_restock_requests' => $pendingRestocks,
                'pending_discrepancies' => $pendingDiscrepancies,
                'low_stock_alerts' => $lowStockCount,
                'expiring_batches' => $expiringBatches,
                'monthly_wastage' => $monthlyWastage,
                'active_reservations' => $activeReservations,
            ],
        ]);
    }

    /**
     * Farm staff dashboard.
     */
    protected function farmStaffDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = today();

        // Today's collections at their farm
        $todayCollections = \App\Models\DailyCollection::where('farm_id', $user->farm_id)
            ->whereDate('collection_date', $today)
            ->selectRaw('COUNT(*) as count, SUM(quantity) as total')
            ->first();

        // Pending restock requests (to acknowledge)
        $pendingRestocks = RestockRequest::where('status', RestockRequest::STATUS_PENDING)->count();

        // Active batches at farm
        $activeBatches = Batch::where('farm_id', $user->farm_id)
            ->where('status', 'active')
            ->count();

        // Expiring batches at farm
        $expiringBatches = Batch::where('farm_id', $user->farm_id)
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->whereBetween('expires_at', [now(), now()->addDays(3)])
            ->count();

        return response()->json([
            'role' => 'farm_staff',
            'data' => [
                'today_collections' => [
                    'count' => $todayCollections->count ?? 0,
                    'total_quantity' => $todayCollections->total ?? 0,
                ],
                'pending_restock_requests' => $pendingRestocks,
                'active_batches' => $activeBatches,
                'expiring_batches' => $expiringBatches,
            ],
        ]);
    }

    /**
     * Shop staff dashboard.
     */
    protected function shopStaffDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $user->shop_id;
        $today = today();

        // Today's sales at their shop
        $todaySales = Sale::completed()
            ->where('shop_id', $shopId)
            ->whereDate('sold_at', $today)
            ->selectRaw('COUNT(*) as count, SUM(total) as revenue')
            ->first();

        // Low stock at their shop
        $lowStockCount = Inventory::where('shop_id', $shopId)
            ->where('available_stock', '>', 0)
            ->get()
            ->filter(fn($i) => $i->isBelowThreshold())
            ->count();

        // Today's pickups
        $todayPickups = Reservation::where('shop_id', $shopId)
            ->whereDate('pickup_date', $today)
            ->whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_READY])
            ->count();

        // In-transit deliveries
        $inTransitDeliveries = \App\Models\Delivery::where('shop_id', $shopId)
            ->where('status', \App\Models\Delivery::STATUS_DISPATCHED)
            ->count();

        // Expiring inventory
        $expiringInventory = Inventory::where('shop_id', $shopId)
            ->where('available_stock', '>', 0)
            ->whereHas('batch', function ($q) {
                $q->whereBetween('expires_at', [now(), now()->addDays(3)]);
            })
            ->count();

        return response()->json([
            'role' => 'shop_staff',
            'data' => [
                'today_sales' => [
                    'count' => $todaySales->count ?? 0,
                    'revenue' => $todaySales->revenue ?? 0,
                ],
                'low_stock_alerts' => $lowStockCount,
                'today_pickups' => $todayPickups,
                'in_transit_deliveries' => $inTransitDeliveries,
                'expiring_inventory' => $expiringInventory,
            ],
        ]);
    }

    /**
     * Customer dashboard.
     */
    protected function customerDashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Active reservations
        $activeReservations = Reservation::where('customer_id', $user->id)
            ->active()
            ->count();

        // Recent purchases
        $recentPurchases = Sale::where('customer_id', $user->id)
            ->completed()
            ->count();

        // Total spent
        $totalSpent = Sale::where('customer_id', $user->id)
            ->completed()
            ->sum('total');

        return response()->json([
            'role' => 'customer',
            'data' => [
                'active_reservations' => $activeReservations,
                'total_purchases' => $recentPurchases,
                'total_spent' => $totalSpent,
            ],
        ]);
    }
}
