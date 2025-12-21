<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions matching form requests and policies
        $permissions = [
            // Daily Collections
            'view-collections',
            'create-collection',
            'edit-collection',
            'verify-collection',
            
            // Batches
            'view-batches',
            'create-batch',
            'edit-batch',
            'expire-batch',
            
            // Inventory
            'view-inventory',
            'adjust-inventory',
            'transfer-inventory',
            
            // Restock Requests
            'view-restock-requests',
            'request-restock',
            'acknowledge-restock',
            'cancel-restock',
            
            // Deliveries
            'view-deliveries',
            'dispatch-delivery',
            'receive-delivery',
            'report-discrepancy',
            'investigate-discrepancy',
            
            // Wastage
            'view-wastage',
            'log-wastage',
            
            // Reservations
            'view-reservations',
            'create-reservation',
            'edit-reservation',
            'confirm-reservation',
            'cancel-reservation',
            
            // Sales
            'view-sales',
            'create-sale',
            'void-sale',
            'refund-sale',
            
            // Reports
            'view-reports',
            'view-analytics',
            
            // Settings
            'manage-categories',
            'manage-users',
            'manage-shops',
            'manage-farms',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        
        // Farm Staff Role - Based on RBAC matrix
        $farmStaff = Role::firstOrCreate(['name' => 'farm_staff', 'guard_name' => 'web']);
        $farmStaff->syncPermissions([
            'view-collections',
            'create-collection',
            'edit-collection',
            'view-batches',
            'create-batch',
            'edit-batch',
            'view-inventory',
            'view-restock-requests',
            'acknowledge-restock',
            'dispatch-delivery',
            'view-deliveries',
            'report-discrepancy',
            'view-wastage',
            'log-wastage',
        ]);

        // Shop Staff Role - Based on RBAC matrix
        $shopStaff = Role::firstOrCreate(['name' => 'shop_staff', 'guard_name' => 'web']);
        $shopStaff->syncPermissions([
            'view-inventory',
            'view-restock-requests',
            'request-restock',
            'view-deliveries',
            'receive-delivery',
            'report-discrepancy',
            'view-wastage',
            'log-wastage',
            'view-reservations',
            'confirm-reservation',
            'cancel-reservation',
            'view-sales',
            'create-sale',
            'view-reports',
        ]);

        // Manager Role - Full access
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions($permissions);

        // Customer Role - Limited access
        $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $customer->syncPermissions([
            'view-reservations',
            'create-reservation',
            'edit-reservation',
            'cancel-reservation',
            'view-sales', // View own purchases
        ]);
    }
}
