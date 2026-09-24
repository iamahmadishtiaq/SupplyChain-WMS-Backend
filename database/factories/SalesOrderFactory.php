<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SalesOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'warehouse_id' => Warehouse::factory(),
            'created_by' => User::factory(),
            'order_number' => 'SO-' . now()->format('Ym') . '-' . strtoupper(Str::random(4)),
            'status' => 'pending',
            'subtotal' => 0.00,
            'tax_amount' => 0.00,
            'shipping_fee' => 0.00,
            'total_amount' => 0.00,
            'shipping_notes' => fake()->sentence(),
        ];
    }
}