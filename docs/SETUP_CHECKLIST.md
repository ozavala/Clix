# Setup Checklist (Clix)

Use this checklist to bootstrap a fresh workspace while preserving the architecture.

## Prerequisites
- PHP, Composer, Node/NPM installed
- MySQL/MariaDB (or preferred DB) credentials ready

## Install & Configure
- Create project or open existing folder
- Copy `.env.example` to `.env` and set:
  - DB_* variables
  - APP_URL, APP_KEY (run `php artisan key:generate`)

## Packages (if needed)
- Spatie Permission (optional or if using `database/migrations/2025_07_06_224202_create_permission_tables.php`):
  - `composer require spatie/laravel-permission`
  - Publish config (optional): `php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider"`

## Migrations (order hints)
1) Users
   - `database/migrations/0001_01_01_000000_create_users_table.php` (PK `user_id`)
2) Tenants
   - `2025_07_06_224000_create_tenants_table.php` (PK `tenant_id`)
3) CRM Users
   - `2025_07_06_224023_create_crm_users_table.php` (PK `crm_user_id`, FK to `users.user_id` & `tenants.tenant_id`)
4) Roles/Permissions
   - Spatie or custom role/permission tables (ensure foreign keys align)

Run:
```bash
php artisan migrate
```

## Model Contracts
- `App\Models\User`
  - PK: `user_id`
  - `crmProfile(): hasOne(CrmUser::class)`
- `App\Models\CrmUser`
  - PK: `crm_user_id`
  - `belongsTo(User::class, 'user_id', 'user_id')`
  - `belongsTo(Tenant::class, 'tenant_id', 'tenant_id')`
  - `roles(): belongsToMany(UserRole::class, 'crm_user_user_role', 'crm_user_id', 'role_id')`
- `App\Models\Tenant`
  - Cast `address`, `settings` as arrays

## Factories
- `TenantFactory` must JSON-encode `address` and `settings`.
- `CrmUserFactory`
  - Provide `user_id` via `User::factory()`
  - Provide `forTenant(Tenant $tenant)` state
- `ProductFactory` (if used)
  - Accepts/creates tenant and ensures `created_by_user_id` is a CRM user in same tenant

## Seed Data
- Default Tenant
- Default CRM Admin user (linked to the tenant)
- Assign `admin` role to the CRM user (not an `is_admin` flag)

Run:
```bash
php artisan db:seed
# or for a clean slate
php artisan migrate:fresh --seed
```

## Middleware & Auth
- `App\Http\Middleware\EnsureUserHasCrmAccess` to restrict CRM routes to users with `crmProfile`
- `App\Http\Controllers\Auth\LoginController` routes authenticated users to CRM or regular dashboard based on `crmProfile`

## Verification
- Create a test tenant and CRM user via factories
- Confirm:
  - `crm_users.tenant_id` is never null
  - JSON fields in `tenants` insert as strings and cast properly in the model
  - Roles/permissions attach/detach as expected

## Git Hygiene
- Commit a snapshot after setup:
```bash
git add .
git commit -m "Bootstrap: base migrations, models, factories, seeders, docs"
```

## Troubleshooting Tips
- If you see "Array to string conversion", ensure JSON fields are encoded in factories and cast in models
- If you see FK errors, verify migration order and FK column names (`tenant_id`, `user_id`, `crm_user_id`)
- If Spatie tables conflict with custom ones, decide on a single roles system and remove the other
