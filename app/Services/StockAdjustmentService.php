<?php

namespace App\Services;
use App\Models\StockAdjustment;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public function adjustStock(int $stockBatchId, string $type, int $quantityAdjusted, string $reason, User $user): StockAdjustment
    {
        if ($quantityAdjusted === 0) {
            throw new Exception("Adjustment quantity cannot be zero.");
        }

        return DB::transaction(function () use ($stockBatchId, $type, $quantityAdjusted, $reason, $user){
            $batch = StockBatch::where('id', $stockBatchId)
                ->lockForUpdate()
                ->firstOrFail();

            $before = $batch->quantity_on_hand;
            $after = $before + $quantityAdjusted;

            if ($after < 0) {
                throw new Exception("Adjustment would result in negative stock. Current on-hand: {$before}");
            }

            if ($quantityAdjusted < 0) {
                $available = $batch->quantity_on_hand - $batch->quantity_reserved;
                if (abs($quantityAdjusted) > $available) {
                    throw new Exception("Cannot deduct " . abs($quantityAdjusted) . " units. {$batch->quantity_reserved} units are already reserved for sales orders.");
                }
            }

            // Financial impact = unit_cost * quantity_adjusted
            $financialImpact = round($batch->unit_cost * $quantityAdjusted, 2);

            // 1. Update Batch Quantity
            $batch->quantity_on_hand = $after;
            $batch->save();

            // 2. Create Stock Adjustment Entry
            $adjustment = StockAdjustment::create([
                'stock_batch_id' => $batch->id,
                'user_id' => $user->id,
                'type' => $type,
                'quantity_before' => $before,
                'quantity_adjusted' => $quantityAdjusted,
                'quantity_after' => $after,
                'financial_impact' =>  $financialImpact,
                'reason' => $reason,
            ]);

            // 3. Immutable Stock Movement Ledger Entry
            StockMovement::create([
                'stock_batch_id' => $batch->id,
                'user_id' => $user->id,
                'type' => 'adjustment',
                'quantity_change' => $quantityAdjusted,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'source_location_id' => $batch->location_id,
                'destination_location_id' => null,
                'reference_type' => StockAdjustment::class,
                'reference_id' => $adjustment->id,
                'reason' => "Adjustment [{$type}] : {$reason}",
                'created_at' => now(),
            ]);

            return $adjustment->load(['batch.variant', 'batch.location']);
        });
    }
}
