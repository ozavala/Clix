<?php

namespace Tests\Concerns;

use App\Models\CrmUser;
use App\Models\User;
use App\Models\Tenant;
use App\Models\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait HandlesTenantAuthentication
{
    protected $tenant;
    protected $adminUser;
    protected $adminRole;

    /**
     * Set up the test environment with a tenant and admin user
     */
    protected function setUpTenant(): void
    {
        // Create or get test tenant
        $this->tenant = Tenant::firstOrCreate(
            ['name' => 'Test Tenant'],
            [
                'legal_id' => '11-1111111-001',
                'is_active' => true,
                'address' => '123 Test St, Test City',
                'phone' => '123-456-7890',
                'website' => 'https://test-tenant.example.com',
                'email' => 'admin@test-tenant.example.com',
                'industry' => 'Technology',
                'settings' => json_encode(['currency' => 'USD']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create admin role
        $this->adminRole = UserRole::firstOrCreate(
            ['name' => 'Admin'],
            [
                'description' => 'Administrator with full access',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        // Try to find existing admin user first
        $this->adminUser = CrmUser::where('email', 'admin@test-tenant.example.com')->first();

        // If user doesn't exist, create a new base user and crm user with a unique username
        if (!$this->adminUser) {
            $baseUser = User::firstOrCreate(
                ['email' => 'admin@test-tenant.example.com'],
                [
                    'password' => bcrypt('password'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $this->adminUser = CrmUser::create([
                'user_id' => $baseUser->user_id,
                'username' => 'testadmin' . now()->timestamp, // Ensure unique username
                'email' => 'admin@test-tenant.example.com',
                'full_name' => 'Test Admin',
                'password' => bcrypt('password'),
                'tenant_id' => $this->tenant->id,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Assign admin role to user using direct insert to avoid model events
        $pivotExists = DB::table('crm_user_user_role')
            ->where('tenant_id', $this->tenant->id)
            ->where('user_id', $this->adminUser->user_id)
            ->where('role_id', $this->adminRole->role_id)
            ->exists();
        
        if (!$pivotExists) {
            DB::table('crm_user_user_role')->insert([
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->adminUser->user_id,
                'role_id' => $this->adminRole->role_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Authenticate as admin user
        $this->actingAs($this->adminUser, 'crm');
        
        // Set tenant context
        request()->merge(['tenant_id' => $this->tenant->id]);
        config(['tenant_id' => $this->tenant->id]);
        
        // Set the current tenant in the application container
        app()->instance('currentTenant', $this->tenant);
    }

    /**
     * Create a test user with specific permissions
     */
    protected function createUserWithPermissions(array $permissions = [], array $userData = []): CrmUser
    {
        $user = CrmUser::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
        ], $userData));

        $role = UserRole::factory()->create([
            'name' => 'Test Role ' . uniqid(),
            'tenant_id' => $this->tenant->id,
        ]);

        $role->permissions()->sync(
            \App\Models\Permission::whereIn('name', $permissions)->pluck('id')
        );

        $user->roles()->attach($role);

        return $user;
    }

    /**
     * Act as a specific user
     */
    public function actingAsUser(CrmUser $user): self
    {
        $this->actingAs($user, 'crm');
        return $this;
    }

    /**
     * Act as a guest user
     */
    public function actingAsGuest($guard = null): self
    {
        parent::actingAsGuest($guard);
        return $this;
    }
}
