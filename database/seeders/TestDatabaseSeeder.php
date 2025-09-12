<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\UserRole;
use App\Models\CrmUser;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class TestDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test tenant
        $tenant = Tenant::firstOrCreate(
            ['name' => 'Test Tenant'],
            [
                'legal_id' => '11-1111111-001',
                'is_active' => true,
                'address' => '123 Test St, Test City',
                'phone' => '123-456-7890',
                'website' => 'https://test-tenant.example.com',
                'email' => 'info@test-tenant.example.com',
                'industry' => 'Technology',
                'settings' => json_encode(['currency' => 'USD']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create admin role
        $adminRole = UserRole::firstOrCreate(
            ['name' => 'Admin'],
            [
                'description' => 'Administrator with full access',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        // Attach tenant to role through the pivot table if needed
        if (Schema::hasTable('tenant_user_role')) {
            $adminRole->tenants()->syncWithoutDetaching([$tenant->id]);
        }

        // Try to find existing admin user first
        $adminUser = CrmUser::where('email', 'test@example.com')->first();
        
        // If user doesn't exist, create a new one with a unique username
        if (!$adminUser) {
            $adminUser = CrmUser::create([
                'user_id' => 1,
                'username' => 'testadmin' . now()->timestamp, // Ensure unique username
                'email' => 'test@example.com',
                'full_name' => 'Test Admin',
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign admin role to user using direct insert to avoid model events
        $pivotExists = DB::table('crm_user_user_role')
            ->where('crm_user_id', $adminUser->user_id)
            ->where('role_id', $adminRole->role_id)
            ->exists();
            
        if (!$pivotExists) {
            DB::table('crm_user_user_role')->insert([
                'crm_user_id' => $adminUser->user_id,
                'role_id' => $adminRole->role_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign all permissions to admin role
        $permissions = Permission::all();
        $adminRole->permissions()->sync($permissions->pluck('id'));

        // Seed other test data
        $this->call([
            TaxRateSeeder::class,
            ProductCategorySeeder::class,
            ProductFeatureSeeder::class,
            WarehouseSeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
