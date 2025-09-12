<?php

namespace Tests;

use App\Models\CrmUser;
use App\Models\Tenant;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected $tenant;
    protected $adminUser;
    protected $adminRole;
    protected $salesRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Add the ShareErrorsFromSession middleware to handle $errors variable in views
        $this->withMiddleware(\Illuminate\View\Middleware\ShareErrorsFromSession::class);

        // Create test tenant
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

        $this->salesRole = UserRole::firstOrCreate(
            ['name' => 'Sales'],
            [
                'description' => 'Sales representative',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create admin user with a unique username
        $username = 'testadmin_' . uniqid();
        $this->adminUser = CrmUser::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'username' => $username,
                'full_name' => 'Test Admin',
                'password' => bcrypt('password'),
                'tenant_id' => $this->tenant->id,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Assign admin role to user
        $pivotExists = DB::table('crm_user_user_role')
            ->where('crm_user_id', $this->adminUser->user_id)
            ->where('role_id', $this->adminRole->role_id)
            ->exists();
            
        if (!$pivotExists) {
            DB::table('crm_user_user_role')->insert([
                'crm_user_id' => $this->adminUser->user_id,
                'role_id' => $this->adminRole->role_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Set the tenant_id for the current request
        request()->merge(['tenant_id' => $this->tenant->id]);
        config(['tenant_id' => $this->tenant->id]);
    }

    /**
     * Create a test user with specific permissions
     */
    protected function createUserWithPermissions(array $permissions = [], array $userData = [])
    {
        $user = CrmUser::factory()->create(array_merge([
            'tenant_id' => $this->tenant->id,
        ], $userData));

        $role = UserRole::factory()->create([
            'name' => 'Test Role ' . uniqid(),
            'tenant_id' => $this->tenant->id,
        ]);

        $role->permissions()->sync(
            Permission::whereIn('name', $permissions)->pluck('id')
        );

        $user->roles()->attach($role);

        return $user;
    }

    /**
     * Asigna uno o varios permisos a un usuario CrmUser para pruebas.
     * Si el permiso no existe, lo crea. Si el usuario no tiene un rol, se le asigna uno temporal.
     * El permiso se asigna al rol del usuario.
     *
     * @param \App\Models\CrmUser $user
     * @param string|array $permissions Nombre(s) del permiso a asignar (ej: 'edit-settings')
     */
    protected function givePermission($user, $permissions)
    {
        $permissions = (array) $permissions;
        $role = $user->roles()->first();
        if (!$role) {
            // Crear un rol temporal si el usuario no tiene ninguno
            $role = \App\Models\UserRole::create([
                'name' => 'TestRole_' . uniqid(),
                'description' => 'Rol temporal para testing',
            ]);
            $user->roles()->attach($role);
        }
        foreach ($permissions as $permName) {
            $permission = \App\Models\Permission::firstOrCreate([
                'name' => $permName
            ], [
                'description' => 'Permiso temporal para testing'
            ]);
            // Asignar el permiso al rol si no lo tiene
            if (!$role->permissions()->where('name', $permName)->exists()) {
                $role->permissions()->attach($permission);
            }
        }
        // Refrescar relaciones
        $user->load('roles.permissions');
    }
}
