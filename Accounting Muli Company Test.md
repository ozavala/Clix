# Accounting Multi-Company Test — Review and Recommendations

This report reviews the logic in `tests/Feature/AccountingMultiCompanyTest.php` and provides targeted recommendations to make the tests correct, robust, and aligned with the current application routes and data model.

## Summary of Findings

- The test correctly aims to validate multi-tenant isolation for Accounts, Journal Entries, Tax Reports and Financial Reports across two companies and a super admin user.
- There are several mismatches between the test assumptions and the actual application structure, especially route names and tenant scoping. These will cause false failures.
- A few model details (e.g., primary key of `CrmUser`) are handled correctly in places, but `is_super_admin` mass assignment can fail due to model fillable settings.

## Strengths

- __Clear multi-company scenarios__: Each test block sets up data for company 1 and company 2 and then verifies isolation and super admin visibility.
- __Use of `RefreshDatabase`__: Ensures tests are deterministic.
- __Consistent use of factories where appropriate__: For users; could be extended to other models as well.

## Issues and Risks Identified

1. __Route name mismatches__
   - Test references:
     - `accounts.index` — not defined in `routes/web.php`.
     - `tax-reports.monthly` — not defined; the existing route name is `iva.report.monthly` under the `reportes/iva` prefix.
     - `financial-reports.income-statement` — not defined; there is `reports.profit_and_loss` which may be the intended endpoint.
   - Confirmed existing routes in `routes/web.php`:
     - `Route::resource('journal-entries', JournalEntryController::class)` — OK (`journal-entries.index`, `journal-entries.store`, etc.).
     - Tax monthly report: `Route::get('/mensual', [TaxReportController::class, 'monthly'])->name('iva.report.monthly')`.
     - Profit & loss: `Route::get('reports/profit-and-loss', [ProfitAndLossController::class, 'index'])->name('reports.profit_and_loss')`.

2. __Tenant context not switched when switching user__
   - In `tests/TestCase.php`, `setUp()` sets `request()->merge(['tenant_id' => $this->tenant->id]);` and `config(['tenant_id' => $this->tenant->id]);` using a default tenant created by the base test.
   - In `AccountingMultiCompanyTest`, you `actingAs($this->user1)` or `actingAs($this->user2)`, but you do not update the request/config tenant context accordingly. If controllers rely on `request('tenant_id')` or `config('tenant_id')` for scoping (which is common), the data may be scoped to the wrong tenant, causing false positives/negatives.

3. __`CrmUser` mass-assignment for `is_super_admin`__
   - The model `app/Models/CrmUser.php` defines `$fillable` without `is_super_admin`. Creating users with `['is_super_admin' => true]` (as the test does) risks a `MassAssignmentException` or simply ignores the attribute, depending on how the model is configured.
   - The factory also has a `superAdmin()` state that sets `is_super_admin`, which has the same risk.

4. __Brittle HTML content assertions__
   - Using `$response->assertSee('5000.00')` or literal names (e.g., `'Cash - Company 1'`) binds tests to formatting/presentation and locale. This is fragile especially with `resources/lang/` present (en/es) and potential formatting differences.

5. __Domain behavior for Super Admin may not align__
   - The tests assume super admin can see both companies’ data in a single call to endpoints intended to be tenant-scoped. Application routes/middleware may still scope results by tenant even for super admin, unless special handling exists.

## Concrete Recommendations

1. __Fix route names in the tests to match the app__
   - Replace `accounts.index` with a valid route or create the missing route. If Accounts listing exists, add a resource route for `AccountController`:
     ```php
     // routes/web.php
     Route::resource('accounts', AccountController::class);
     ```
     Otherwise, adjust the test to hit whatever UI/API actually lists accounts.
   - Replace `tax-reports.monthly` with `iva.report.monthly`.
   - Replace `financial-reports.income-statement` with `reports.profit_and_loss` if that’s the intended check. If you truly need an income statement endpoint, create it with the appropriate route name and controller action.

2. __Switch tenant context when switching user__
   - After each `actingAs($user)`, set the tenant context to the user’s tenant:
     ```php
     // In tests, a small helper can keep this DRY
     private function actAsWithTenant(\App\Models\CrmUser $user): void
     {
         $this->actingAs($user);
         request()->merge(['tenant_id' => $user->tenant_id]);
         config(['tenant_id' => $user->tenant_id]);
     }
     ```
     Then replace calls like `$this->actingAs($this->user1);` with `$this->actAsWithTenant($this->user1);`.

3. __Allow mass assignment of `is_super_admin` or set it explicitly__
   - Option A: Add `is_super_admin` to `$fillable` in `app/Models/CrmUser.php`:
     ```php
     protected $fillable = [
         'tenant_id', 'username', 'full_name', 'email', 'password', 'locale', 'is_super_admin'
     ];
     ```
   - Option B: In tests, set the attribute post-creation to bypass mass-assignment:
     ```php
     $user = CrmUser::factory()->create([...]);
     $user->forceFill(['is_super_admin' => true])->save();
     ```

4. __Reduce reliance on presentation-layer assertions__
   - Prefer asserting view data, JSON payloads, or DB state over `assertSee` on literal text/numbers:
     - If controllers return view data, use `assertViewHas` to check totals/collections.
     - If controllers are JSON-capable, hit JSON endpoints and assert specific fields.
     - For isolation, assert via database queries filtered by `tenant_id` that only the expected records exist.

5. __Clarify super admin visibility rules__
   - If super admin should see all companies’ data in one request, ensure controllers detect `is_super_admin` and do not restrict by tenant, or implement a way to pass “all tenants” in the request.
   - Alternatively, modify tests so super admin verifies each tenant’s data by switching context explicitly, which is often a clearer design.

6. __Use factories for Accounts and other models where possible__
   - Replace manual `Account::create([...])` with factories if available, to simplify setup and align with defaults (e.g., active status, codes):
     ```php
     $this->asset1 = Account::factory()->forTenant($this->company1)->asset()->create();
     ```
     If factories don’t exist yet, consider adding them as this test grows.

7. __Consistency on primary keys__
   - You correctly used `$user->user_id` when writing `created_by_user_id`. Keep this consistent across all test inserts. In some places in the test you used `$this->user1->id` (e.g., Tax models). That should be `$this->user1->user_id` to match the model’s primary key.

## Suggested Code Adjustments (Illustrative Snippets)

- __Tenant context helper in the test class__:
```php
private function actAsWithTenant(\App\Models\CrmUser $user): void
{
    $this->actingAs($user);
    request()->merge(['tenant_id' => $user->tenant_id]);
    config(['tenant_id' => $user->tenant_id]);
}
```

- __Fix super admin creation__:
```php
$this->superAdmin = CrmUser::factory()->create([
    'tenant_id' => $this->company1->id,
]);
$this->superAdmin->forceFill(['is_super_admin' => true])->save();
```

- __Fix route names__:
```php
// Tax monthly
$response = $this->get(route('iva.report.monthly', [
    'year' => now()->year,
    'month' => now()->month,
]));

// Profit & Loss (if this is your “income statement”)
$response = $this->get(route('reports.profit_and_loss', [
    'from' => now()->startOfMonth()->format('Y-m-d'),
    'to' => now()->endOfMonth()->format('Y-m-d'),
]));
```

- __Prefer DB assertions for isolation__:
```php
$this->assertDatabaseHas('journal_entries', [
    'tenant_id' => $this->company1->id,
    'description' => 'Revenue Entry for Company 1',
]);
$this->assertDatabaseMissing('journal_entries', [
    'tenant_id' => $this->company1->id,
    'description' => 'Revenue Entry for Company 2',
]);
```

## Optional Enhancements

- __Add dedicated policies/middleware tests__: Verify `authorize` behavior for permissions like `view-accounts` or `create-journal-entries` at the controller/policy layer.
- __Add negative tests__: Attempt cross-tenant access by ID parameters and assert 403/404.
- __Seed alignment__: The test seeds `SettingsTableSeeder`; ensure seeder doesn’t introduce data that pollutes assertions. Consider test-specific seeders.

## Action Plan

1. Update route names in the test to `journal-entries.*`, `iva.report.monthly`, and `reports.profit_and_loss`, or add the missing routes/controllers.
2. Introduce the `actAsWithTenant()` helper and use it whenever switching users, or adopt the app’s tenant-switching mechanism in tests.
3. Address `is_super_admin` mass-assignment (fillable or `forceFill()` in tests).
4. Replace brittle `assertSee()` checks with DB assertions and/or `assertViewHas()` / JSON assertions.
5. Normalize use of `user_id` vs `id` in all user references.

These changes will make the test robust, aligned with your routes, and accurately validate multi-company isolation and super admin behavior.
