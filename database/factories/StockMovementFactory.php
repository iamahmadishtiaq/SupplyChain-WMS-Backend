<?php

namespace Database\Factories;

use App\Models\StockBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stock_batch_id' => StockBatch::factory(),
            'user_id' => User::factory(),
            'type' => 'inbound',
            'quantity_change' => 100,
            'quantity_before' => 0,
            'quantity_after' => 100,
            'source_location_id' => null,
            'destination_location_id' => null,
            'reference_type' => null,
            'reference_id' => null,
            'reason' => 'Initial stock receipt',
            'created_at' => now(),
        ];
    }
}