# Clix Project Summary (Snapshot Before Rebuild)

This document captures the current architecture and key decisions so you can resume work seamlessly in a new folder/repo.

## Architecture Overview
- Multi-tenant application where `tenants` primary key is `tenant_id`.
- Authentication `User` is distinct from CRM profile `CrmUser`.
- `CrmUser` has its own PK `crm_user_id` and references `users.user_id` via `crm_users.user_id`.
- Roles/permissions are handled via roles and a pivot table (no `is_admin` column on users).

## Key Models
- `App\Models\Tenant`
  - PK: `tenant_id`
  - JSON fields stored as strings in DB and cast as arrays in model: `address`, `settings`.
  - Example casts in model:
    ```php
    protected $casts = [
        'is_active' => 'boolean',
        'subscription_ends_at' => 'datetime',
        'address' => 'array',
        'settings' => 'array',
    ];
    ```

- `App\Models\User` (authentication)
  - PK: `user_id` (migration: `database/migrations/0001_01_01_000000_create_users_table.php`)
  - Relationship: `crmProfile(): hasOne(CrmUser::class)`

- `App\Models\CrmUser` (CRM profile)
  - PK: `crm_user_id`
  - FK: `user_id` -> `users.user_id`
  - FK: `tenant_id` -> `tenants.tenant_id`
  - Belongs to `User` and `Tenant`
  - Roles relationship: `belongsToMany(UserRole::class, 'crm_user_user_role', 'crm_user_id', 'role_id')`

- `App\Models\UserRole`
  - Used for role-based access; permissions linked via `permission_user_role` pivot.

## Migrations (essential)
- `users` table (PK `user_id`).
- `crm_users` table (PK `crm_user_id`, FK `user_id` to `users.user_id`, FK `tenant_id` to `tenants.tenant_id`).
- `tenants` table (PK `tenant_id`).
- Role/permission tables:
  - If using Spatie: `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`.
  - Custom: `user_roles`, `crm_user_user_role`, `permission_user_role` with tenant scoping.

## Factories
- `TenantFactory`
  - Ensures `address` and `settings` are JSON-encoded strings when inserting.
- `CrmUserFactory`
  - Always sets `tenant_id` and `user_id` correctly.
  - Provides `forTenant(Tenant $tenant)` state.
- `ProductFactory`
  - Uses a consistent tenant; prefers existing tenant or creates one.
  - Ensures `created_by_user_id` references a CRM user belonging to the same tenant.

## Seeders
- Create a default tenant and a CRM admin user tied to that tenant.
- Assign an `admin` role to the CRM user (via roles/permissions system), instead of using an `is_admin` column.

## Authentication and Access
- Login via `User` credentials.
- Middleware `EnsureUserHasCrmAccess` ensures an authenticated `User` has a related `CrmUser` profile before accessing CRM routes.
- Controller example: `Auth\LoginController` checks `crmProfile` to route user appropriately.

## Notes and Gotchas
- Ensure all references to tenant primary key use `tenant_id` (not `id`).
- When factories create related records, pass the same tenant explicitly using `forTenant($tenant)` states.
- Use model casts (array) for JSON fields to avoid "Array to string conversion" errors.
- Keep role/permission assignments scoped correctly (per tenant if applicable).

## Next Steps When Rebuilding
- Initialize fresh Laravel project and copy over:
  - Migrations for `users`, `tenants`, `crm_users`, role/permission tables.
  - Models and relationships described above.
  - Factories and seeders respecting tenant scoping.
  - Middleware and auth controller logic for CRM access.
- Install packages if using Spatie Permissions.
- Seed default tenant and CRM admin user with `admin` role.

---
This summary is the canonical reference of decisions made before the rebuild. Update as architecture evolves.
