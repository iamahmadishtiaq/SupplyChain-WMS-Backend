<?php

namespace App\Services;
use App\Events\OrderDispatched;
use App\Jobs\GenerateDispatchInvoiceJob;
use App\Models\StockMovement;
use App\Models\SalesOrder;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderFulfillmentService
{
    public function dispatchOrder(SalesOrder $order, User $user): SalesOrder
    {
        if ($order->status !== 'allocated') {
            throw new Exception("Only allocated orders can be dispatched. Current status: {$order->status}");
        }

        return DB::transaction(function () use ($order, $user) {
            foreach ($order->items as $item) {
                foreach ($item->allocations as $allocation) {
                    $batch = $allocation->batch;
                    $qty = $allocation->quantity_allocated;

                    $qtyBefore = $batch->quantity_on_hand;
                    $qtyAfter = $qtyBefore - $qty;

                    // 1. Release reservation and deduct actual on-hand stock
                    $batch->decrement('quantity_reserved', $qty);
                    $batch->decrement('quantity_on_hand', $qty);

                    // 2. Mark allocation as picked
                    $allocation->update(['status' => 'picked']);

                    // 3. Polymorphic Stock Movement Ledger entry
                    StockMovement::create([
                        'stock_batch_id' => $batch->id,
                        'user_id' => $user->id,
                        'type' => 'outbound',
                        'quantity_change' => -$qty,
                        'quantity_before' => $qtyBefore,
                        'quantity_after' => $qtyAfter,
                        'source_location_id' => $batch->location_id,
                        'destination_location_id' => null,
                        'reference_type' => SalesOrder::class,
                        'reference_id' => $order->id,
                        'reason' => "Order fulfillment for #{$order->order_number} via Batch {$batch->batch_number}",
                        'created_at' => now(),
                    ]);
                }
            }

            $order->status = 'dispatched';
            $order->save();

            // Fire event for notifications and background queue jobs
            event(new OrderDispatched($order));
            GenerateDispatchInvoiceJob::dispatch($order);

            return $order->load(['items.allocations.batch', 'stockMovements']);
        });
    }
}
