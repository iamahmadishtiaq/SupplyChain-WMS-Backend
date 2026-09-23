<?php

namespace App\Services;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GoodsReceivingService
{
    /**
     * Create a new class instance.
     */
    public function receive(PurchaseOrder $purchaseOrder, array $receiptItems, User $user): PurchaseOrder
    {
        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            throw new Exception("Cannot receive items for a PO that is already {$purchaseOrder->status}.");
        }
        return DB::transaction(function () use ($purchaseOrder, $receiptItems, $user){
            foreach ($receiptItems as $receipt) {
                $poItem = $purchaseOrder->items()
                    ->where('id', $receipt['item_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $receivedQty = (int) $receipt['quantity'];
                $pendingQty = $poItem->quantity_ordered - $poItem->quantity_received;

                if ($receivedQty > $pendingQty) {
                    throw new Exception("Received quantity {$receivedQty} exceeds pending ordered quantity ({$pendingQty}) for item ID {$poItem->id}.");
                }

                // 1. Create Stock Batch
                $batchNumber = 'BTH-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

                $batch = StockBatch::create([
                    'product_variant_id' => $poItem->product_variant_id,
                    'location_id' => $receipt['location_id'],
                    'batch_number' => $batchNumber,
                    'quantity_received' => $receivedQty,
                    'quantity_on_hand' => $receivedQty,
                    'quantity_reserved' => 0,
                    'unit_cost' => $poItem->unit_cost,
                    'manufactured_at' => $receipt['manufactured_at'] ?? null,
                    'expires_at' => $receipt['expires_at'] ?? null,
                ]);

                // 2. Log polymorphic movement audit ledger

                StockMovement::create([
                    'stock_batch_id' => $batch->id,
                    'user_id' => $user->id,
                    'type' => 'inbound',
                    'quantity_change' => $receivedQty,
                    'quantity_before' => 0,
                    'quantity_after' => $receivedQty,
                    'source_location_id' => null,
                    'destination_location_id' => $receipt['location_id'],
                    'reference_type' => PurchaseOrder::class,
                    'reference_id' => $purchaseOrder->id,
                    'reason' => "PO Deleivery receipt for #{purchaseOrder->po_number} via Batch {$batchNumber}",
                    'created_at' => now(),
                ]);

                $poItem->increment('quantity_received', $receivedQty);
            }

            $purchaseOrder->refresh();
            $allItemsFullyReceived = $purchaseOrder->items->every(function ($item) {
                return $item->quantity_received >= $item->quantity_ordered;
            });

            $purchaseOrder->status = $allItemsFullyReceived ? 'received' : 'partially_received';
            $purchaseOrder->save();

            return $purchaseOrder->load(['items.variant', 'stockMovements']);
        });
    }
}
