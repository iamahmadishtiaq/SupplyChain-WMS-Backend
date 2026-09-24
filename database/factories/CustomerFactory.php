<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'contact_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'billing_address' => fake()->address(),
            'shipping_address' => fake()->address(),
            'credit_limit' => fake()->randomElement([500000, 1000000, 2500000, 5000000]),
            'is_active' => true,
        ];
    }
}