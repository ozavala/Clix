<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CrmUser;
use App\Models\JournalEntry;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfitAndLossTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $company1;
    protected Tenant $company2;
    protected CrmUser $user1;
    protected CrmUser $user2;
    protected CrmUser $superAdmin;

    private function actAsWithTenant(\App\Models\CrmUser $user): void
    {
        $this->actingAs($user);
        request()->merge(['tenant_id' => $user->tenant_id]);
        config(['tenant_id' => $user->tenant_id]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Tenants
        $this->company1 = Tenant::create([
            'name' => 'Company One',
            'legal_id' => 'TAX-001',
            'phone' => '123-456-7890',
            'address' => '123 Main St',
        ]);
        $this->company2 = Tenant::create([
            'name' => 'Company Two',
            'legal_id' => 'TAX-002',
            'phone' => '987-654-3210',
            'address' => '456 Oak Ave',
        ]);

        // Users
        $this->user1 = CrmUser::factory()->create(['tenant_id' => $this->company1->id]);
        $this->user2 = CrmUser::factory()->create(['tenant_id' => $this->company2->id]);

        $this->superAdmin = CrmUser::factory()->create(['tenant_id' => $this->company1->id]);
        $this->superAdmin->forceFill(['is_super_admin' => true])->save();

        // Accounts company 1
        $asset1 = Account::create([
            'name' => 'Cash - C1',
            'account_number' => '1000',
            'type' => 'asset',
            'code' => 'C1CASH',
            'is_active' => true,
            'tenant_id' => $this->company1->id,
        ]);
        $income1 = Account::create([
            'name' => 'Sales - C1',
            'account_number' => '4000',
            'type' => 'income',
            'code' => 'C1REV',
            'is_active' => true,
            'tenant_id' => $this->company1->id,
        ]);
        $expense1 = Account::create([
            'name' => 'Office Supplies - C1', // Not treated as Costos
            'account_number' => '5000',
            'type' => 'expense',
            'code' => 'C1EXP',
            'is_active' => true,
            'tenant_id' => $this->company1->id,
        ]);

        // Accounts company 2
        $asset2 = Account::create([
            'name' => 'Cash - C2',
            'account_number' => '1000',
            'type' => 'asset',
            'code' => 'C2CASH',
            'is_active' => true,
            'tenant_id' => $this->company2->id,
        ]);
        $income2 = Account::create([
            'name' => 'Sales - C2',
            'account_number' => '4000',
            'type' => 'income',
            'code' => 'C2REV',
            'is_active' => true,
            'tenant_id' => $this->company2->id,
        ]);
        $expense2 = Account::create([
            'name' => 'Office Supplies - C2',
            'account_number' => '5000',
            'type' => 'expense',
            'code' => 'C2EXP',
            'is_active' => true,
            'tenant_id' => $this->company2->id,
        ]);

        // Company 1 entries: ingresos 5000, gastos 2000
        $this->actAsWithTenant($this->user1);
        $je1 = JournalEntry::create([
            'tenant_id' => $this->company1->id,
            'entry_date' => now(),
            'transaction_type' => 'Manual',
            'description' => 'C1 Revenue',
            'created_by_user_id' => $this->user1->user_id,
        ]);
        $je1->lines()->createMany([
            ['tenant_id' => $this->company1->id, 'account_code' => $asset1->code, 'debit_amount' => 5000.00, 'credit_amount' => 0.00],
            ['tenant_id' => $this->company1->id, 'account_code' => $income1->code, 'debit_amount' => 0.00, 'credit_amount' => 5000.00],
        ]);
        $je2 = JournalEntry::create([
            'tenant_id' => $this->company1->id,
            'entry_date' => now(),
            'transaction_type' => 'Manual',
            'description' => 'C1 Expense',
            'created_by_user_id' => $this->user1->user_id,
        ]);
        $je2->lines()->createMany([
            ['tenant_id' => $this->company1->id, 'account_code' => $expense1->code, 'debit_amount' => 2000.00, 'credit_amount' => 0.00],
            ['tenant_id' => $this->company1->id, 'account_code' => $asset1->code, 'debit_amount' => 0.00, 'credit_amount' => 2000.00],
        ]);

        // Company 2 entries: ingresos 8000, gastos 3000
        $this->actAsWithTenant($this->user2);
        $je3 = JournalEntry::create([
            'tenant_id' => $this->company2->id,
            'entry_date' => now(),
            'transaction_type' => 'Manual',
            'description' => 'C2 Revenue',
            'created_by_user_id' => $this->user2->user_id,
        ]);
        $je3->lines()->createMany([
            ['tenant_id' => $this->company2->id, 'account_code' => $asset2->code, 'debit_amount' => 8000.00, 'credit_amount' => 0.00],
            ['tenant_id' => $this->company2->id, 'account_code' => $income2->code, 'debit_amount' => 0.00, 'credit_amount' => 8000.00],
        ]);
        $je4 = JournalEntry::create([
            'tenant_id' => $this->company2->id,
            'entry_date' => now(),
            'transaction_type' => 'Manual',
            'description' => 'C2 Expense',
            'created_by_user_id' => $this->user2->user_id,
        ]);
        $je4->lines()->createMany([
            ['tenant_id' => $this->company2->id, 'account_code' => $expense2->code, 'debit_amount' => 3000.00, 'credit_amount' => 0.00],
            ['tenant_id' => $this->company2->id, 'account_code' => $asset2->code, 'debit_amount' => 0.00, 'credit_amount' => 3000.00],
        ]);
    }

    #[Test]
    public function normal_user_sees_only_own_tenant_data()
    {
        $this->actAsWithTenant($this->user1);
        $response = $this->get(route('reports.profit_and_loss', [
            'from' => now()->startOfMonth()->format('Y-m-d'),
            'to' => now()->endOfMonth()->format('Y-m-d'),
        ]));
        $response->assertOk();
        $response->assertViewHas('ingresos', function ($v) { return abs($v - 5000.00) < 0.01; });
        $response->assertViewHas('gastos', function ($v) { return abs($v - 2000.00) < 0.01; });
        $response->assertViewHas('utilidadNeta', function ($v) { return abs($v - 3000.00) < 0.01; });

        $this->actAsWithTenant($this->user2);
        $response = $this->get(route('reports.profit_and_loss', [
            'from' => now()->startOfMonth()->format('Y-m-d'),
            'to' => now()->endOfMonth()->format('Y-m-d'),
        ]));
        $response->assertOk();
        $response->assertViewHas('ingresos', function ($v) { return abs($v - 8000.00) < 0.01; });
        $response->assertViewHas('gastos', function ($v) { return abs($v - 3000.00) < 0.01; });
        $response->assertViewHas('utilidadNeta', function ($v) { return abs($v - 5000.00) < 0.01; });
    }

    #[Test]
    public function super_admin_can_filter_by_tenant()
    {
        $this->actingAs($this->superAdmin);
        // Super admin filtered by tenant_id = company1
        $response = $this->get(route('reports.profit_and_loss', [
            'from' => now()->startOfMonth()->format('Y-m-d'),
            'to' => now()->endOfMonth()->format('Y-m-d'),
            'tenant_id' => $this->company1->id,
        ]));
        $response->assertOk();
        $response->assertViewHas('ingresos', function ($v) { return abs($v - 5000.00) < 0.01; });
        $response->assertViewHas('gastos', function ($v) { return abs($v - 2000.00) < 0.01; });
    }
}
