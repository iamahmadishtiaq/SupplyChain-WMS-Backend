<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'created_by' => User::factory(),
            'po_number' => 'PO-' . now()->format('Ym') . '-' . strtoupper(Str::random(4)),
            'status' => 'draft',
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addDays(7)->toDateString(),
            'subtotal' => 0.00,
            'tax_amount' => 0.00,
            'shipping_cost' => 0.00,
            'total_amount' => 0.00,
            'notes' => fake()->sentence(),
        ];
    }
}