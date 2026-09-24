<?php

namespace App\Services;

use App\Models\OrderAllocation;
use App\Models\StockBatch;
use App\Models\SalesOrder;
use Exception;
use Illuminate\Support\Facades\DB;

class StockAllocationService
{
    /**
     * Create a new class instance.
     */
    public function allocate(SalesOrder $order): SalesOrder
    {
        if ($order->status !== 'pending') {
            throw new Exception("Only pending orders can be allocated. Current status: {$order->status}");
        }

        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $neededQuantity =  $item->quantity_ordered - $item->quantity_allocated;

                if ($neededQuantity <= 0) {
                    continue;
                }

                // FIFO/FEFO Query with Pessimistic Locking
                $batches = StockBatch::where('product_variant_id', $item->product_variant_id)
                    ->whereRaw('(quantity_on_hand - quantity_reserved) > 0')
                    ->whereHas('location.zone', function ($query) use ($order) {
                        $query->where('warehouse_id', $order->warehouse_id);
                    })
                    ->orderByRaw('expires_at IS NULL, expires_at ASC')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $totalAvailable = $batches->sum(fn ($b) => $b->quantity_on_hand - $b->quantity_reserved);

                if ($totalAvailable < $neededQuantity) {
                    throw new Exception("Insufficent stock for SKU [{$item->variant->sku}]. Required: {$neededQuantity}, Available: {$totalAvailable}");
                }

                foreach ($batches as $batch) {
                    if ($neededQuantity <= 0) {
                        break;
                    }

                    $batchAvailable = $batch->quantity_on_hand - $batch->quantity_reserved;
                    $allocatedFromThisBatch = min($neededQuantity, $batchAvailable);

                    // 1.Reserve quantity on batch
                    $batch->increment('quantity_reserved', $allocatedFromThisBatch);

                    // 2. Create order allocation record
                    OrderAllocation::create([
                        'sales_order_item_id' => $item->id,
                        'stock_batch_id' => $batch->id,
                        'quantity_allocated' => $allocatedFromThisBatch,
                        'status' => 'allocated',
                    ]);

                    // OrderStatus update to 'allocated'
                    $order->status = 'allocated';
                    $order->save();

                    return $order->load(['items.allocations.batch']);
                }
            }
        });
    }
}
