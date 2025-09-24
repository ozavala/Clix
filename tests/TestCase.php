<?php

namespace Tests;

use App\Models\CrmUser;
use App\Models\Tenant;
use App\Models\UserRole;
use App\Models\User;
use App\Models\Permission;
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

        // Bind currentTenant for multi-tenant context in tests
        app()->instance('currentTenant', $this->tenant);

        // Note: Do not create a default CRM admin user here. Individual tests will set up
        // their own users and authentication contexts to avoid collisions with user_id.

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
            Permission::whereIn('name', $permissions)->pluck('permission_id')
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
                'tenant_id' => $this->tenant->id,
            ]);
            $user->roles()->attach($role);
        }
        foreach ($permissions as $permName) {
            $permission = \App\Models\Permission::firstOrCreate([
                'tenant_id' => $this->tenant->id,
                'name' => $permName
            ], [
                'description' => 'Permiso temporal para testing'
            ]);
            // Asignar el permiso al rol si no lo tiene
            if (!$role->permissions()->where('name', $permName)->exists()) {
                $role->permissions()->attach($permission->permission_id, ['tenant_id' => $this->tenant->id]);
            }
        }
        // Refrescar relaciones
        $user->load('roles.permissions');
    }
}
