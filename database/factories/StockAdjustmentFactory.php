<?php

namespace Database\Factories;

use App\Models\StockBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockAdjustmentFactory extends Factory
{
    public function definition(): array
    {
        $batch = StockBatch::first() ?? StockBatch::factory()->create();
        $user = User::first() ?? User::factory()->create();

        $before = $batch->quantity_on_hand;
        $adjusted = fake()->randomElement([-5, -2, 3, 5]);
        $after = max(0, $before + $adjusted);

        return [
            'stock_batch_id' => $batch->id,
            'user_id' => $user->id,
            'type' => $adjusted < 0 ? 'write_off' : 'found_stock',
            'quantity_before' => $before,
            'quantity_adjusted' => $adjusted,
            'quantity_after' => $after,
            'financial_impact' => round($batch->unit_cost * $adjusted, 2),
            'reason' => 'Routine warehouse audit adjustment',
        ];
    }
}