<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Zone;
use Override;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class SupplyChainFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Warehouse $warehouse;
    protected Location $location1;
    protected Location $location2;
    protected ProductVariant $variant;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        // 1. Roles and Permissions setup
        $adminRole = Role::create(['name' => 'super-admin']);
        $permissions = [
            'view-inventory',
            'transfer-stock',
            'create-purchase-order',
            'receive-goods',
            'create-sales-order',
            'allocate-stock',
            'dispatch-order',
            'adjust-stock',
            'view-reports',
        ];

        foreach ($permissions as $perm) {
            Permission::create(['name' => $perm]);
        }
        $adminRole->syncPermissions(Permission::all());

        // 2. Admin User
        $this->adminUser = User::factory()->create([
            'email' => 'admin@wms.test',
        ]);
        $this->adminUser->assignRole($adminRole);

        // 3. Infrastructure
        $this->warehouse = Warehouse::factory()->create();
        $zone = Zone::create([
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Ambient Zone',
            'code' => 'ZN-AMB',
        ]);

        $this->location1 = Location::create([
            'zone_id' => $zone->id,
            'aisle' => 'A1',
            'rack' => 'R1',
            'shelf' => 'S1',
            'bin' => 'B1',
            'barcode' => 'LOC-001',
            'max_weight_capacity_kg' => 1000,
            'is_occupied' => false,
        ]);

        $this->location2 = Location::create([
            'zone_id' => $zone->id,
            'aisle' => 'A1',
            'rack' => 'R1',
            'shelf' => 'S1',
            'bin' => 'B2',
            'barcode' => 'LOC-002',
            'max_weight_capacity_kg' => 1000,
            'is_occupied' => false,
        ]); 

        $category = Category::factory()->create();

        // 4. Catalog
        $product = Product::create([
            'category_id' => 1,
            'name' => 'Industrial Hydraulic Pump',
            'slug' => 'industrial-hydraulic-pump',
            'brand' => 'TechPro',
            'uom' => 'pcs',
            'requires_cold_storage' => false,
            'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'PUMP-BLK-01',
            'barcode' => '09748263828',
            'color' => 'Black',
            'size' => 'Standard',
            'weight_kg' => 5.5,
            'cost_price' => 12000.00,
            'selling_price' => 18500.00,
            'reorder_level' => 10,
        ]);
    }

    // Test full Lifecycle: Inbound Batch -> Internal Bin Transfer -> Sales Order Allocation -> Dispatch
    public function test_complete_warehouse_fulfillment_and_ledger_audit(): void
    {
        $token = $this->adminUser->createToken('test-token')->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        // 1. Initial Stock Batch
        $batch = StockBatch::create([
            'product_variant_id' => $this->variant->id,
            'location_id' => $this->location1->id,
            'batch_number' => 'BTH-TEST-001',
            'quantity_received' => 50,
            'quantity_on_hand' => 50,
            'quantity_reserved' => 0,
            'unit_cost' => 12000.00,
            'manufactured_at' => now()->subMonth()->toDateString(),
            'expires_at' => now()->addYear()->toDateString(),
            'created_at' => now()->subMinutes(10),
        ]);

        // 2. Test Bin Transfer API (Transfer 20 units to Location 2)
        $transferResponse = $this->withHeaders($headers)->postJson('/api/inventory/transfer', [
            'stock_batch_id' => $batch->id,
            'destination_location_id' => $this->location2->id,
            'quantity' => 20,
            'reason' => 'Transferring 20 units to picking bin',
        ]);

        $transferResponse->assertStatus(200);
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'transfer',
            'quantity_change' => 20,
            'source_location_id' => $this->location1->id,
            'destination_location_id' => $this->location2->id,
        ]);

        // Original batch on-hand becomes 30
        $batch->refresh();
        $this->assertEquals(30, $batch->quantity_on_hand);

        // 3. Test Sales Order Creation (Order 10 units)
        $customer = Customer::factory()->create();

        $soResponse = $this->withHeaders($headers)->postJson('/api/sales-orders', [
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'shipping_notes' => 'Test fast delivery',
            'items' => [
                [
                    'product_variant_id' => $this->variant->id,
                    'quantity_ordered' => 10,
                    'unit_price' => 18500.00,
                ],
            ],
        ]);

        $soResponse->assertStatus(201);
        $orderId = $soResponse->json('data.id');

        // 4. Test Stock Allocation (FIFO picks oldest batch: $batch)
        $allocResponse = $this->withHeaders($headers)->postJson("/api/sales-orders/{$orderId}/allocate");
        $allocResponse->assertStatus(200);

        // Check which batch got allocated
        $allocatedBatch = StockBatch::where('quantity_reserved', '>', 0)->first();
        $this->assertNotNull($allocatedBatch);
        $this->assertEquals(10, $allocatedBatch->quantity_reserved);

        $beforeOnHand = $allocatedBatch->quantity_on_hand;

        // 5. Test Order Dispatch
        $dispatchResponse = $this->withHeaders($headers)->postJson("/api/sales-orders/{$orderId}/dispatch");
        $dispatchResponse->assertStatus(200);

        // Stock permanently deducted from the allocated batch
        $allocatedBatch->refresh();
        $this->assertEquals($beforeOnHand - 10, $allocatedBatch->quantity_on_hand);
        $this->assertEquals(0, $allocatedBatch->quantity_reserved);

        // 6. Verify Outbound Movement Ledger
        $this->assertDatabaseHas('stock_movements', [
            'stock_batch_id' => $allocatedBatch->id,
            'type' => 'outbound',
            'quantity_change' => -10,
            'reference_type' => SalesOrder::class,
            'reference_id' => $orderId,
        ]);
    }
}
