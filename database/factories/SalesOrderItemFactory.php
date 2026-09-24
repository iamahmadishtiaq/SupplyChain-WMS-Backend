<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderItemFactory extends Factory
{
    public function definition(): array
    {
        $qty = fake()->numberBetween(5, 30);
        $price = fake()->randomFloat(2, 2000, 8000);

        return [
            'sales_order_id' => SalesOrder::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'quantity_ordered' => $qty,
            'quantity_allocated' => 0,
            'unit_price' => $price,
            'subtotal' => $qty * $price,
        ];
    }
}