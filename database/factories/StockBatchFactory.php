<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StockBatchFactory extends Factory
{
    public function definition(): array
    {
        $qty = fake()->numberBetween(50, 200);

        return [
            'product_variant_id' => ProductVariant::factory(),
            'location_id' => Location::factory(),
            'batch_number' => 'BTH-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
            'quantity_received' => $qty,
            'quantity_on_hand' => $qty,
            'quantity_reserved' => 0,
            'unit_cost' => fake()->randomFloat(2, 500, 15000),
            'manufactured_at' => fake()->dateTimeBetween('-6 months', '-1 month')->format('Y-m-d'),
            'expires_at' => fake()->dateTimeBetween('+6 months', '+2 years')->format('Y-m-d'),
        ];
    }
}