<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Zone;
use App\Services\SkuGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Core Admin / Warehouse Manager User
        $admin = User::firstOrCreate(
            ['email' => 'admin@wms.test'],
            [
                'name' => 'Super Logistics Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // 2. Physical Warehouses & Storage Bin Hierarchy
        $warehouses = Warehouse::factory(2)->create()->each(function ($warehouse) {
            $zones = [
                ['name' => 'Ambient Storage Zone', 'code' => 'ZN-AMB'],
                ['name' => 'High-Value Secure Cage', 'code' => 'ZN-SEC'],
            ];

            foreach ($zones as $zoneData) {
                $zone = Zone::create([
                    'warehouse_id' => $warehouse->id,
                    'name' => $zoneData['name'],
                    'code' => $zoneData['code'],
                ]);

                // Create 5 Standard Storage Bins per Zone
                for ($aisle = 1; $aisle <= 2; $aisle++) {
                    for ($rack = 1; $rack <= 2; $rack++) {
                        Location::create([
                            'zone_id' => $zone->id,
                            'aisle' => "A{$aisle}",
                            'rack' => "R0{$rack}",
                            'shelf' => 'S01',
                            'bin' => 'B01',
                            'barcode' => "LOC-{$warehouse->code}-{$zone->code}-A{$aisle}-R0{$rack}-S01-B01",
                            'max_weight_capacity_kg' => 750.00,
                            'is_occupied' => false,
                        ]);
                    }
                }
            }
        });

        // 3. Suppliers & Customers
        Supplier::factory(5)->create();
        Customer::factory(10)->create();

        // 4. Categories, Products, and Variants with Dynamic SKU
        $categories = Category::factory(3)->create();

        foreach ($categories as $category) {
            $products = Product::create([
                'category_id' => $category->id,
                'name' => fake()->words(2, true) . ' Pro Edition',
                'slug' => fake()->unique()->slug(),
                'brand' => fake()->company(),
                'uom' => 'pcs',
                'requires_cold_storage' => false,
                'is_active' => true,
            ]);

            $colors = ['Black', 'Silver', 'Navy'];
            foreach ($colors as $color) {
                ProductVariant::create([
                    'product_id' => $products->id,
                    'sku' => SkuGeneratorService::generate($products, ['color' => $color]),
                    'barcode' => fake()->unique()->ean13(),
                    'color' => $color,
                    'size' => 'Standard',
                    'weight_kg' => fake()->randomFloat(2, 0.5, 5.0),
                    'cost_price' => 10000.00,
                    'selling_price' => 14500.00,
                    'reorder_level' => 15,
                ]);
            }
        }
    }
}