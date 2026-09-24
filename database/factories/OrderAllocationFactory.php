<?php

namespace Database\Factories;

use App\Models\SalesOrderItem;
use App\Models\StockBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderAllocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sales_order_item_id' => SalesOrderItem::factory(),
            'stock_batch_id' => StockBatch::factory(),
            'quantity_allocated' => fake()->numberBetween(1, 10),
            'status' => 'allocated',
        ];
    }
}