<?php

namespace App\Listeners;

use App\Events\OrderDispatched;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\ProductVariant;
use App\Models\StockBatch;
use Illuminate\Support\Facades\Log;

class CheckLowStockThresholdListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderDispatched $event): void
    {
        $order = $event->order;

        foreach ($order->items as $item) {
            $variant = $item->variant;

            // Total available on-hand stock across all active warehouse batches
            $totalOnHand = StockBatch::where('product_variant_id', $variant->id)->sum('quantity_on_hand');

            if ($totalOnHand <= $variant->reorder_level) {
                Log::warning("LOW STOCK ALERT: Variant [{$variant->sku}] dropped to {$totalOnHand} units. Reorder threshold is {$variant->reorder_level}. Automated PO required!");
            }
        }
    }
}
