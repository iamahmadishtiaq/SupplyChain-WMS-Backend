<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderItemFactory extends Factory
{
    public function definition(): array
    {
        $qty = fake()->numberBetween(20, 100);
        $cost = fake()->randomFloat(2, 1000, 5000);

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'quantity_ordered' => $qty,
            'quantity_received' => 0,
            'unit_cost' => $cost,
            'subtotal' => $qty * $cost,
        ];
    }
}