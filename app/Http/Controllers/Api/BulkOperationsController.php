<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BatchResource;
use App\Http\Resources\InventoryResource;
use App\Models\Batch;
use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulkOperationsController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Bulk expire batches.
     */
    public function expireBatches(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'batch_ids' => ['required', 'array', 'min:1'],
            'batch_ids.*' => ['required', 'integer', 'exists:batches,id'],
        ]);

        $results = [
            'success' => [],
            'failed' => [],
        ];

        DB::transaction(function () use ($validated, &$results, $request) {
            foreach ($validated['batch_ids'] as $batchId) {
                $batch = Batch::find($batchId);

                if (!$batch) {
                    $results['failed'][] = [
                        'id' => $batchId,
                        'reason' => 'Batch not found.',
                    ];
                    continue;
                }

                // Check authorization
                if ($request->user()->cannot('expire', $batch)) {
                    $results['failed'][] = [
                        'id' => $batchId,
                        'reason' => 'Unauthorized to expire this batch.',
                    ];
                    continue;
                }

                if ($batch->status === 'expired') {
                    $results['failed'][] = [
                        'id' => $batchId,
                        'reason' => 'Batch is already expired.',
                    ];
                    continue;
                }

                // Update status and fire event
                $wastedQuantity = $batch->current_quantity;
                $batch->update(['status' => 'expired']);
                
                if ($wastedQuantity > 0) {
                    event(new \App\Events\BatchExpired($batch, $wastedQuantity, $request->user()));
                }
                
                $results['success'][] = $batchId;
            }
        });

        return response()->json([
            'message' => count($results['success']) . ' batches expired successfully.',
            'data' => $results,
        ]);
    }

    /**
     * Bulk delete (soft delete) resources.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resource' => ['required', 'string', 'in:batches,reservations,sales'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
        ]);

        $modelMap = [
            'batches' => \App\Models\Batch::class,
            'reservations' => \App\Models\Reservation::class,
            'sales' => \App\Models\Sale::class,
        ];

        $model = $modelMap[$validated['resource']];
        $policyAction = 'delete';

        $results = [
            'success' => [],
            'failed' => [],
        ];

        DB::transaction(function () use ($validated, $model, $policyAction, &$results, $request) {
            foreach ($validated['ids'] as $id) {
                $record = $model::find($id);

                if (!$record) {
                    $results['failed'][] = [
                        'id' => $id,
                        'reason' => 'Record not found.',
                    ];
                    continue;
                }

                if ($request->user()->cannot($policyAction, $record)) {
                    $results['failed'][] = [
                        'id' => $id,
                        'reason' => 'Unauthorized.',
                    ];
                    continue;
                }

                $record->delete();
                $results['success'][] = $id;
            }
        });

        return response()->json([
            'message' => count($results['success']) . ' records deleted successfully.',
            'data' => $results,
        ]);
    }

    /**
     * Bulk adjust inventory stock.
     */
    public function bulkAdjustInventory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'adjustments' => ['required', 'array', 'min:1'],
            'adjustments.*.inventory_id' => ['required', 'integer', 'exists:inventories,id'],
            'adjustments.*.adjustment' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $results = [
            'success' => [],
            'failed' => [],
        ];

        DB::transaction(function () use ($validated, &$results, $request) {
            foreach ($validated['adjustments'] as $adjustment) {
                $inventory = Inventory::find($adjustment['inventory_id']);

                if (!$inventory) {
                    $results['failed'][] = [
                        'id' => $adjustment['inventory_id'],
                        'reason' => 'Inventory record not found.',
                    ];
                    continue;
                }

                if ($request->user()->cannot('adjust', $inventory)) {
                    $results['failed'][] = [
                        'id' => $adjustment['inventory_id'],
                        'reason' => 'Unauthorized.',
                    ];
                    continue;
                }

                $newStock = $inventory->available_stock + $adjustment['adjustment'];

                if ($newStock < 0) {
                    $results['failed'][] = [
                        'id' => $adjustment['inventory_id'],
                        'reason' => 'Adjustment would result in negative stock.',
                    ];
                    continue;
                }

                $inventory->available_stock = $newStock;
                $inventory->save();

                // Log wastage if reducing stock
                if ($adjustment['adjustment'] < 0) {
                    $this->inventoryService->recordWastage(
                        $inventory->shop_id,
                        $inventory->egg_category_id,
                        abs($adjustment['adjustment']),
                        'bulk_adjustment',
                        $validated['reason'],
                        $request->user()->id,
                        $inventory->batch_id
                    );
                }

                $results['success'][] = [
                    'id' => $adjustment['inventory_id'],
                    'new_stock' => $newStock,
                ];
            }
        });

        return response()->json([
            'message' => count($results['success']) . ' inventory records adjusted successfully.',
            'data' => $results,
        ]);
    }

    /**
     * Bulk update status for reservations.
     */
    public function bulkUpdateReservationStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reservation_ids' => ['required', 'array', 'min:1'],
            'reservation_ids.*' => ['required', 'integer', 'exists:reservations,id'],
            'action' => ['required', 'string', 'in:confirm,cancel,ready'],
            'cancellation_reason' => ['required_if:action,cancel', 'nullable', 'string', 'max:500'],
        ]);

        $results = [
            'success' => [],
            'failed' => [],
        ];

        DB::transaction(function () use ($validated, &$results, $request) {
            foreach ($validated['reservation_ids'] as $reservationId) {
                $reservation = \App\Models\Reservation::find($reservationId);

                if (!$reservation) {
                    $results['failed'][] = [
                        'id' => $reservationId,
                        'reason' => 'Reservation not found.',
                    ];
                    continue;
                }

                if ($request->user()->cannot('update', $reservation)) {
                    $results['failed'][] = [
                        'id' => $reservationId,
                        'reason' => 'Unauthorized.',
                    ];
                    continue;
                }

                try {
                    switch ($validated['action']) {
                        case 'confirm':
                            if (!$reservation->isPending()) {
                                throw new \Exception('Reservation is not pending.');
                            }
                            $reservation->confirm();
                            break;

                        case 'cancel':
                            if (!$reservation->isActive()) {
                                throw new \Exception('Reservation is not active.');
                            }
                            $reservation->cancel(
                                $request->user()->id,
                                $validated['cancellation_reason'] ?? 'Bulk cancellation'
                            );
                            break;

                        case 'ready':
                            if ($reservation->status !== \App\Models\Reservation::STATUS_CONFIRMED) {
                                throw new \Exception('Reservation is not confirmed.');
                            }
                            $reservation->markReady();
                            break;
                    }

                    $results['success'][] = $reservationId;
                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'id' => $reservationId,
                        'reason' => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'message' => count($results['success']) . ' reservations updated successfully.',
            'data' => $results,
        ]);
    }
}
