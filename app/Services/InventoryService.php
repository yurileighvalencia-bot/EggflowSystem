<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\EggCategory;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\User;
use App\Models\RestockRequest;
use App\Models\WastageLog;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class InventoryService
{
    /**
     * Deduct stock using FIFO (First-In, First-Out) with row-level locking.
     * This prevents concurrent overselling by using SELECT FOR UPDATE.
     *
     * @param int $shopId
     * @param int $categoryId
     * @param int $quantity
     * @return Collection Collection of deductions [{batch_id, quantity}]
     * @throws InsufficientStockException
     */
    public function deductStock(int $shopId, int $categoryId, int $quantity): Collection
    {
        return DB::transaction(function () use ($shopId, $categoryId, $quantity) {
            // Lock inventory rows for this shop and category, ordered by batch collection date (FIFO)
            $inventories = Inventory::where('shop_id', $shopId)
                ->where('egg_category_id', $categoryId)
                ->where('available_stock', '>', 0)
                ->join('batches', 'inventories.batch_id', '=', 'batches.id')
                ->where('batches.status', 'active')
                ->orderBy('batches.collection_date', 'asc')
                ->orderBy('batches.id', 'asc')
                ->select('inventories.*')
                ->lockForUpdate()
                ->get();

            $remaining = $quantity;
            $deductions = collect();

            foreach ($inventories as $inventory) {
                if ($remaining <= 0) {
                    break;
                }

                $deduct = min($inventory->available_stock, $remaining);
                
                $inventory->available_stock -= $deduct;
                $inventory->save();

                // Update batch quantity too
                if ($inventory->batch) {
                    $inventory->batch->current_quantity -= $deduct;
                    if ($inventory->batch->current_quantity <= 0) {
                        $inventory->batch->status = 'depleted';
                    }
                    $inventory->batch->save();
                }

                $deductions->push([
                    'inventory_id' => $inventory->id,
                    'batch_id' => $inventory->batch_id,
                    'quantity' => $deduct,
                ]);

                $remaining -= $deduct;
            }

            if ($remaining > 0) {
                throw new InsufficientStockException(
                    "Insufficient stock for category ID {$categoryId}. " .
                    "Requested: {$quantity}, Available: " . ($quantity - $remaining)
                );
            }

            // Check if we need to trigger a restock request
            $this->checkAndTriggerRestock($shopId, $categoryId);

            return $deductions;
        });
    }

    /**
     * Reserve stock for a customer reservation.
     *
     * @param int $shopId
     * @param int $categoryId
     * @param int $quantity
     * @return bool
     * @throws InsufficientStockException
     */
    public function reserveStock(int $shopId, int $categoryId, int $quantity): bool
    {
        return DB::transaction(function () use ($shopId, $categoryId, $quantity) {
            $inventories = Inventory::where('shop_id', $shopId)
                ->where('egg_category_id', $categoryId)
                ->where('available_stock', '>', 0)
                ->join('batches', 'inventories.batch_id', '=', 'batches.id')
                ->where('batches.status', 'active')
                ->orderBy('batches.collection_date', 'asc')
                ->select('inventories.*')
                ->lockForUpdate()
                ->get();

            $remaining = $quantity;

            foreach ($inventories as $inventory) {
                if ($remaining <= 0) {
                    break;
                }

                $reserve = min($inventory->available_stock, $remaining);
                
                $inventory->available_stock -= $reserve;
                $inventory->reserved_stock += $reserve;
                $inventory->save();

                $remaining -= $reserve;
            }

            if ($remaining > 0) {
                throw new InsufficientStockException(
                    "Insufficient stock to reserve for category ID {$categoryId}. " .
                    "Requested: {$quantity}, Available: " . ($quantity - $remaining)
                );
            }

            return true;
        });
    }

    /**
     * Release reserved stock back to available.
     *
     * @param int $shopId
     * @param int $categoryId
     * @param int $quantity
     * @return bool
     */
    public function releaseReservedStock(int $shopId, int $categoryId, int $quantity): bool
    {
        return DB::transaction(function () use ($shopId, $categoryId, $quantity) {
            $inventories = Inventory::where('shop_id', $shopId)
                ->where('egg_category_id', $categoryId)
                ->where('reserved_stock', '>', 0)
                ->lockForUpdate()
                ->get();

            $remaining = $quantity;

            foreach ($inventories as $inventory) {
                if ($remaining <= 0) {
                    break;
                }

                $release = min($inventory->reserved_stock, $remaining);
                
                $inventory->reserved_stock -= $release;
                $inventory->available_stock += $release;
                $inventory->save();

                $remaining -= $release;
            }

            return true;
        });
    }

    /**
     * Fulfill reserved stock (customer picks up reservation).
     *
     * @param int $shopId
     * @param int $categoryId
     * @param int $quantity
     * @return Collection
     */
    public function fulfillReservedStock(int $shopId, int $categoryId, int $quantity): Collection
    {
        return DB::transaction(function () use ($shopId, $categoryId, $quantity) {
            $inventories = Inventory::where('shop_id', $shopId)
                ->where('egg_category_id', $categoryId)
                ->where('reserved_stock', '>', 0)
                ->join('batches', 'inventories.batch_id', '=', 'batches.id')
                ->orderBy('batches.collection_date', 'asc')
                ->select('inventories.*')
                ->lockForUpdate()
                ->get();

            $remaining = $quantity;
            $deductions = collect();

            foreach ($inventories as $inventory) {
                if ($remaining <= 0) {
                    break;
                }

                $fulfill = min($inventory->reserved_stock, $remaining);
                
                $inventory->reserved_stock -= $fulfill;
                $inventory->save();

                // Update batch quantity
                if ($inventory->batch) {
                    $inventory->batch->current_quantity -= $fulfill;
                    if ($inventory->batch->current_quantity <= 0) {
                        $inventory->batch->status = 'depleted';
                    }
                    $inventory->batch->save();
                }

                $deductions->push([
                    'inventory_id' => $inventory->id,
                    'batch_id' => $inventory->batch_id,
                    'quantity' => $fulfill,
                ]);

                $remaining -= $fulfill;
            }

            return $deductions;
        });
    }

    /**
     * Add stock to inventory (from delivery).
     *
     * @param int $shopId
     * @param int $categoryId
     * @param int $batchId
     * @param int $quantity
     * @param float|null $unitPrice
     * @return Inventory
     */
    public function addStock(int $shopId, int $categoryId, int $batchId, int $quantity, ?float $unitPrice = null): Inventory
    {
        return DB::transaction(function () use ($shopId, $categoryId, $batchId, $quantity, $unitPrice) {
            $inventory = Inventory::firstOrNew([
                'shop_id' => $shopId,
                'egg_category_id' => $categoryId,
                'batch_id' => $batchId,
            ]);

            $inventory->available_stock += $quantity;
            
            if ($unitPrice !== null) {
                $inventory->unit_price = $unitPrice;
            } elseif (!$inventory->unit_price) {
                $category = EggCategory::find($categoryId);
                $inventory->unit_price = $category->default_price ?? 0;
            }

            $inventory->save();

            return $inventory;
        });
    }

    /**
     * Get total stock for a category at a shop.
     *
     * @param int $shopId
     * @param int $categoryId
     * @return array
     */
    public function getStockSummary(int $shopId, int $categoryId): array
    {
        $result = Inventory::where('shop_id', $shopId)
            ->where('egg_category_id', $categoryId)
            ->selectRaw('SUM(available_stock) as available, SUM(reserved_stock) as reserved')
            ->first();

        $available = $result->available ?? 0;
        $reserved = $result->reserved ?? 0;

        $category = EggCategory::find($categoryId);

        return [
            'available' => $available,
            'reserved' => $reserved,
            'total' => $available + $reserved,
            'threshold' => $category->low_stock_threshold ?? 0,
            'is_low' => $available <= ($category->low_stock_threshold ?? 0),
        ];
    }

    /**
     * Check stock levels and trigger restock request if below threshold.
     *
     * @param int $shopId
     * @param int $categoryId
     * @return RestockRequest|null
     */
    public function checkAndTriggerRestock(int $shopId, int $categoryId): ?RestockRequest
    {
        $summary = $this->getStockSummary($shopId, $categoryId);
        
        if (!$summary['is_low']) {
            return null;
        }

        // Check if there's already an active restock request
        if (RestockRequest::hasActiveRequest($shopId, $categoryId)) {
            return null;
        }

        $category = EggCategory::find($categoryId);
        
        return RestockRequest::create([
            'shop_id' => $shopId,
            'egg_category_id' => $categoryId,
            'quantity_requested' => $category->restock_quantity ?? 1000,
            'quantity_remaining' => $category->restock_quantity ?? 1000,
            'status' => RestockRequest::STATUS_PENDING,
        ]);
    }

    /**
     * Record wastage and deduct from inventory.
     *
     * @param int $shopId
     * @param int $categoryId
     * @param int $quantity
     * @param string $source
     * @param string $reason
     * @param int $loggedBy
     * @param int|null $batchId
     * @param int|null $deliveryId
     * @return WastageLog
     */
    public function recordWastage(
        int $shopId,
        int $categoryId,
        int $quantity,
        string $source,
        string $reason,
        int $loggedBy,
        ?int $batchId = null,
        ?int $deliveryId = null
    ): WastageLog {
        return DB::transaction(function () use ($shopId, $categoryId, $quantity, $source, $reason, $loggedBy, $batchId, $deliveryId) {
            // Deduct from inventory if it's shop spoilage
            if ($source === WastageLog::SOURCE_SHOP_SPOILAGE && $batchId) {
                $inventory = Inventory::where('shop_id', $shopId)
                    ->where('egg_category_id', $categoryId)
                    ->where('batch_id', $batchId)
                    ->lockForUpdate()
                    ->first();

                if ($inventory && $inventory->available_stock >= $quantity) {
                    $inventory->available_stock -= $quantity;
                    $inventory->save();
                }
            }

            return WastageLog::create([
                'shop_id' => $shopId,
                'batch_id' => $batchId,
                'delivery_id' => $deliveryId,
                'egg_category_id' => $categoryId,
                'quantity' => $quantity,
                'source' => $source,
                'reason' => $reason,
                'logged_by' => $loggedBy,
                'logged_at' => now(),
            ]);
        });
    }

    /**
     * Get inventory with expiring batches.
     *
     * @param int $shopId
     * @param int $daysUntilExpiry
     * @return Collection
     */
    public function getExpiringInventory(int $shopId, int $daysUntilExpiry = 3): Collection
    {
        return Inventory::where('shop_id', $shopId)
            ->where('available_stock', '>', 0)
            ->whereHas('batch', function ($query) use ($daysUntilExpiry) {
                $query->whereBetween('expires_at', [now(), now()->addDays($daysUntilExpiry)]);
            })
            ->with(['batch', 'eggCategory'])
            ->get();
    }
}
