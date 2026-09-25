<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockAdjustment;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Zone;
use App\Services\SkuGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Setup Roles, Permissions, & The 3 Users
        $this->call(RoleAndPermissionSeeder::class);

        $admin = User::where('email', 'admin@wms.test')->first();
        $manager = User::where('email', 'manager@wms.test')->first();
        $operator = User::where('email', 'operator@wms.test')->first();

        // 2. Warehouses, Zones, & Locations
        $warehouses = Warehouse::factory(2)->create();
        $allLocations = collect();

        foreach ($warehouses as $warehouse) {
            $zones = [
                ['name' => 'Ambient Storage Zone', 'code' => 'ZN-AMB'],
                ['name' => 'Cold Chain Vault', 'code' => 'ZN-COLD'],
            ];

            foreach ($zones as $zoneData) {
                $zone = Zone::create([
                    'warehouse_id' => $warehouse->id,
                    'name' => $zoneData['name'],
                    'code' => $zoneData['code'],
                ]);

                for ($aisle = 1; $aisle <= 2; $aisle++) {
                    for ($rack = 1; $rack <= 2; $rack++) {
                        $allLocations->push(Location::create([
                            'zone_id' => $zone->id,
                            'aisle' => "A{$aisle}",
                            'rack' => "R0{$rack}",
                            'shelf' => 'S01',
                            'bin' => 'B01',
                            'barcode' => "LOC-{$warehouse->code}-{$zone->code}-A{$aisle}-R0{$rack}-S01-B01",
                            'max_weight_capacity_kg' => 1000.00,
                            'is_occupied' => false,
                        ]));
                    }
                }
            }
        }

        // 3. Suppliers & Customers
        $suppliers = Supplier::factory(4)->create();
        $customers = Customer::factory(6)->create();

        // 4. Categories, Products, & Variants
        $categories = Category::factory(3)->create();
        $allVariants = collect();

        foreach ($categories as $category) {
            for ($p = 1; $p <= 2; $p++) {
                $product = Product::create([
                    'category_id' => $category->id,
                    'name' => fake()->words(2, true) . ' Series ' . $p,
                    'slug' => fake()->unique()->slug(),
                    'brand' => fake()->company(),
                    'uom' => 'pcs',
                    'requires_cold_storage' => false,
                    'is_active' => true,
                ]);

                foreach (['Black', 'White'] as $color) {
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => SkuGeneratorService::generate($product, ['color' => $color]),
                        'barcode' => fake()->unique()->ean13(),
                        'color' => $color,
                        'size' => 'Standard',
                        'weight_kg' => fake()->randomFloat(2, 0.5, 3.0),
                        'cost_price' => 5000.00,
                        'selling_price' => 8500.00,
                        'reorder_level' => 20,
                    ]);
                    $allVariants->push($variant);
                }
            }
        }

        // 5. Inbound PO & Active Stock Batches
        $primaryWarehouse = $warehouses->first();
        $primarySupplier = $suppliers->first();

        $po = PurchaseOrder::create([
            'supplier_id' => $primarySupplier->id,
            'warehouse_id' => $primaryWarehouse->id,
            'created_by' => $manager->id,
            'po_number' => 'PO-' . now()->format('Ym') . '-0001',
            'status' => 'received',
            'order_date' => now()->subDays(5)->toDateString(),
            'expected_delivery_date' => now()->subDays(2)->toDateString(),
            'subtotal' => 500000.00,
            'tax_amount' => 0.00,
            'total_amount' => 500000.00,
        ]);

        $createdBatches = collect();

        foreach ($allVariants->take(4) as $index => $variant) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_variant_id' => $variant->id,
                'quantity_ordered' => 100,
                'quantity_received' => 100,
                'unit_cost' => $variant->cost_price,
                'subtotal' => 100 * $variant->cost_price,
            ]);

            $batchLoc = $allLocations[$index % $allLocations->count()];

            $batch = StockBatch::create([
                'product_variant_id' => $variant->id,
                'location_id' => $batchLoc->id,
                'batch_number' => 'BTH-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'quantity_received' => 100,
                'quantity_on_hand' => 100,
                'quantity_reserved' => 0,
                'unit_cost' => $variant->cost_price,
                'manufactured_at' => now()->subMonths(1)->toDateString(),
                'expires_at' => now()->addYear()->toDateString(),
            ]);

            $createdBatches->push($batch);

            StockMovement::create([
                'stock_batch_id' => $batch->id,
                'user_id' => $operator->id,
                'type' => 'inbound',
                'quantity_change' => 100,
                'quantity_before' => 0,
                'quantity_after' => 100,
                'source_location_id' => null,
                'destination_location_id' => $batchLoc->id,
                'reference_type' => PurchaseOrder::class,
                'reference_id' => $po->id,
                'reason' => "Initial PO receiving for #{$po->po_number}",
                'created_at' => now(),
            ]);
        }

        // 6. Outbound Sales Order (Pending Allocation)
        $primaryCustomer = $customers->first();
        $so = SalesOrder::create([
            'customer_id' => $primaryCustomer->id,
            'warehouse_id' => $primaryWarehouse->id,
            'created_by' => $manager->id,
            'order_number' => 'SO-' . now()->format('Ym') . '-0001',
            'status' => 'pending',
            'subtotal' => 85000.00,
            'tax_amount' => 0.00,
            'shipping_fee' => 0.00,
            'total_amount' => 85000.00,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_variant_id' => $allVariants->first()->id,
            'quantity_ordered' => 10,
            'quantity_allocated' => 0,
            'unit_price' => 8500.00,
            'subtotal' => 85000.00,
        ]);

        // 7. Initial Seeded Stock Adjustment Record
        $sampleBatch = $createdBatches->first();
        if ($sampleBatch) {
            StockAdjustment::create([
                'stock_batch_id' => $sampleBatch->id,
                'user_id' => $manager->id,
                'type' => 'write_off',
                'quantity_before' => 100,
                'quantity_adjusted' => -2,
                'quantity_after' => 98,
                'financial_impact' => -10000.00,
                'reason' => 'Quality check scrap sample',
            ]);

            $sampleBatch->update(['quantity_on_hand' => 98]);
        }
    }
}