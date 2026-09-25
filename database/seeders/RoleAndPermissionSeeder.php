<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Permission cache clear karein
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Tamam System Permissions
        $permissions = [
            'view-inventory',
            'transfer-stock',
            'create-purchase-order',
            'receive-goods',
            'create-sales-order',
            'allocate-stock',
            'dispatch-order',
            'adjust-stock',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // 2. Roles Create Karein
        $adminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $managerRole = Role::firstOrCreate(['name' => 'warehouse-manager']);
        $operatorRole = Role::firstOrCreate(['name' => 'warehouse-operator']);

        // 3. Permissions Map Karein
        $adminRole->syncPermissions(Permission::all());

        $managerRole->syncPermissions([
            'view-inventory',
            'transfer-stock',
            'create-purchase-order',
            'receive-goods',
            'create-sales-order',
            'allocate-stock',
            'adjust-stock',
        ]);

        $operatorRole->syncPermissions([
            'view-inventory',
            'transfer-stock',
            'allocate-stock',
            'dispatch-order',
        ]);

        // 4. Teeno Test Users Create Karein Aur Roles Assign Karein
        $admin = User::firstOrCreate(
            ['email' => 'admin@wms.test'],
            [
                'name' => 'Super Logistics Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles([$adminRole]);

        $manager = User::firstOrCreate(
            ['email' => 'manager@wms.test'],
            [
                'name' => 'Warehouse Manager',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $manager->syncRoles([$managerRole]);

        $operator = User::firstOrCreate(
            ['email' => 'operator@wms.test'],
            [
                'name' => 'Warehouse Floor Operator',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $operator->syncRoles([$operatorRole]);
    }
}