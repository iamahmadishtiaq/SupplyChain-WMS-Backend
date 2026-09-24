<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Consumer Electronics', 'Industrial Machinery', 'Pharmaceuticals', 'Apparel & Textiles', 'Automotive Parts']);
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 4)),
            'description' => fake()->sentence(),
        ];
    }
}