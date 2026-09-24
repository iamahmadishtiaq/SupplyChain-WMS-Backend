<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions define karein
        $permissions = [
            'view-inventory',
            'transfer-stock',
            'create-purchase-order',
            'receive-goods',
            'create-sales-order',
            'allocate-stock',
            'dispatch-order',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles create karein
        $adminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $managerRole = Role::firstOrCreate(['name' => 'warehouse-manager']);
        $operatorRole = Role::firstOrCreate(['name' => 'warehouse-operator']);

        // Permissions assign karein
        $adminRole->syncPermissions(Permission::all());

        $managerRole->syncPermissions([
            'view-inventory',
            'transfer-stock',
            'create-purchase-order',
            'receive-goods',
            'create-sales-order',
            'allocate-stock',
        ]);

        $operatorRole->syncPermissions([
            'view-inventory',
            'transfer-stock',
            'allocate-stock',
            'dispatch-order',
        ]);

        // Default users ko roles assign karein
        $adminUser = User::where('email', 'admin@wms.test')->first();
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
        }

        $operatorUser = User::where('email', 'operator@wms.test')->first();
        if ($operatorUser) {
            $operatorUser->assignRole($operatorRole);
        }
    }
}