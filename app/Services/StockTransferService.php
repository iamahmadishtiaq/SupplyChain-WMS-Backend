<?php

namespace App\Services;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Location;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    // Transfer stock batch quantity from one bin to another location.
    public function transfer(int $stockBatchId, int $destinationLocationId, int $quantity, User $user, ?string $reason = null): StockBatch
    {
        return DB::transaction(function () use ($stockBatchId, $destinationLocationId, $quantity, $user, $reason){
            $batch = StockBatch::where('id', $stockBatchId)
                ->lockForUpdate()
                ->firstOrFail();

            $destinationLocation = Location::findOrFail($destinationLocationId);

            $availableQty = $batch->quantity_on_hand - $batch->quantity_reserved;

            if ($quantity > $availableQty) {
                throw new Exception("Cannot transfer {$quantity} units. Only {$availableQty} unreserved units available in Batch #{$batch->batch_number}.");
            }

            if ($batch->location_id === $destinationLocationId) {
                throw new Exception("Source and destination locations cannot be identical");
            }

            $sourceLocationId = $batch->location_id;

            //Scenario A: whole batch moved
            if ($quantity === $batch->quantity_on_hand && $batch->quantity_reserved === 0) {
                $batch->location_id = $destinationLocationId;
                $batch->save();

                $targetBatch = $batch;
            } else{
                // Scenario B: Partial transfer - deduct from original batch and make sub-batch at new location
                $batch->decrement('quantity_on_hand', $quantity);

                $targetBatch = StockBatch::create([
                    'product_variant_id' => $batch->product_variant_id,
                    'location_id' => $destinationLocationId,
                    'batch_number' => $batch->batch_number . '-T',
                    'quantity_received' => $quantity,
                    'quantity_on_hand' => $quantity,
                    'quantity_reserved' => 0,
                    'unit_cost' => $batch->unit_cost,
                    'manufactured_at' => $batch->manufactured_at,
                    'expires_at' => $batch->expires_at,
                ]);
            }

            StockMovement::create([
                'stock_batch_id' => $targetBatch->id,
                'user_id' => $user->id,
                'type' => 'transfer',
                'quantity_change' => $quantity,
                'quantity_before' => $batch->quantity_on_hand + $quantity,
                'quantity_after' => $batch->quantity_on_hand,
                'source_location_id' => $sourceLocationId,
                'destination_location_id' => $destinationLocation->id,
                'reference_type' => null,
                'reference_id' => null,
                'reason' => $reason ?? "Internet stock relocation from Loc #{$sourceLocationId} to Loc #{$destinationLocation->id}",
                'created_at' => now(),
            ]);

            return $targetBatch->load(['location', 'variant']);
        });
    }
}
