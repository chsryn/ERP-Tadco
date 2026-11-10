<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permission granular modul Purchasing (SCM-09).
     * Nama mengikuti pola <modul>.<aksi> agar bisa diperluas ke modul lain.
     */
    public const PURCHASING_PERMISSIONS = [
        'suppliers.view',
        'suppliers.create',
        'suppliers.update',
        'suppliers.delete',
        'purchase-orders.view',
        'purchase-orders.create',
        'purchase-orders.update',
        'purchase-orders.confirm',
        'purchase-orders.cancel',
        'purchase-orders.print',
        'auto-po.view',
        'auto-po.create',
        'purchase-receipts.view',
        'purchase-receipts.create',
        'purchase-receipts.process-stock-in',
    ];

    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'sales']);
        Role::firstOrCreate(['name' => 'finance']);

        foreach (self::PURCHASING_PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin->givePermissionTo(self::PURCHASING_PERMISSIONS);
    }
}
