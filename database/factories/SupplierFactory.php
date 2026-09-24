<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'code' => 'SUP-' . fake()->unique()->numerify('####'),
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'tax_number' => 'NTN-' . fake()->numerify('#######-#'),
            'lead_time_days' => fake()->numberBetween(3, 14),
            'is_active' => true,
        ];
    }
}