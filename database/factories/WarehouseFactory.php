<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WarehouseFactory extends Factory
{
    public function definition(): array
    {
        $city = fake()->city();
        return [
            'name' => "{$city} Distribution Hub",
            'code' => 'WH-' . strtoupper(Str::random(3)) . '-' . fake()->numerify('##'),
            'city' => $city,
            'address' => fake()->streetAddress(),
            'manager_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'is_active' => true,
        ];
    }
}