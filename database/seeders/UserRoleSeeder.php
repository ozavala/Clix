<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UserRole;
use App\Models\Permission;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first tenant or create one if none exists
    $tenant = \App\Models\Tenant::first() ?? \App\Models\Tenant::factory()->create();
        
    // Create Roles
        $adminRole = UserRole::create([
            'tenant_id' => $tenant->tenant_id,
            'name' => 'Admin',
            'description' => 'Administrator with full access',
        ]);

        $salesRole = UserRole::create([
            'tenant_id' => 1,
            'name' => 'Sales',
            'description' => 'Sales team member with access to customer and sales modules',
        ]);

        $supportRole = UserRole::create([
            'tenant_id' => 1,
            'name' => 'Support',
            'description' => 'Support team member with access to customer and support related modules',
        ]);

        // Fetch all permissions
        $permissions = Permission::all();

        // Assign all permissions to Admin role
        if ($adminRole) {
            $adminRole->permissions()->attach($permissions->pluck('permission_id'));
        }

        // Assign specific permissions to Sales role
        if ($salesRole) {
            $salesPermissions = Permission::whereIn('name', [
                'view-customers', 'create-customers', 'edit-customers',
                'view-leads', 'create-leads', 'edit-leads',
            ])->pluck('permission_id');
            $salesRole->permissions()->attach($salesPermissions);
        }

        // Assign specific permissions to Support role
        if ($supportRole) {
            $supportPermissions = Permission::whereIn('name', [
                'view-customers', 'edit-customers', // Example: Support might view/edit but not create/delete
                'view-leads',
            ])->pluck('permission_id');
            $supportRole->permissions()->attach($supportPermissions);
        }
    }
}