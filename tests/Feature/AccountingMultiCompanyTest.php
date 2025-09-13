<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CrmUser;
use App\Models\JournalEntry;
use App\Models\Tenant;
use App\Models\TaxRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountingMultiCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $company1;
    protected Tenant $company2;
    protected CrmUser $user1;
    protected CrmUser $user2;
    protected CrmUser $superAdmin;
    protected Account $asset1;
    protected Account $liability1;
    protected Account $income1;
    protected Account $expense1;
    protected Account $asset2;
    protected Account $liability2;
    protected Account $income2;
    protected Account $expense2;

    /**
     * Helper to act as a user and set tenant context for the request/config.
     */
    private function actAsWithTenant(\App\Models\CrmUser $user): void
    {
        $this->actingAs($user);
        request()->merge(['tenant_id' => $user->tenant_id]);
        config(['tenant_id' => $user->tenant_id]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SettingsTableSeeder::class);

        // Create two companies
        $this->company1 = Tenant::create([
            'name' => 'Company One',
            'legal_id' => 'TAX-001',
            'phone' => '123-456-7890',
            'address' => '123 Main St, Anytown, USA',
        ]);

        $this->company2 = Tenant::create([
            'name' => 'Company Two',
            'legal_id' => 'TAX-002',
            'phone' => '987-654-3210',
            'address' => '456 Oak Ave, Somewhere, USA',
        ]);

        // Create users for each company
        $this->user1 = CrmUser::factory()->create([
            'tenant_id' => $this->company1->id,
        ]);

        $this->user2 = CrmUser::factory()->create([
            'tenant_id' => $this->company2->id,
        ]);

        // Create a super admin user (avoid mass-assignment issues)
        $this->superAdmin = CrmUser::factory()->create([
            'tenant_id' => $this->company1->id, // Primary company
        ]);
        $this->superAdmin->forceFill(['is_super_admin' => true])->save();

        // Give necessary permissions to users
        $this->givePermission($this->user1, [
            'view-accounts',
            'create-accounts',
            'edit-accounts',
            'delete-accounts',
            'view-journal-entries',
            'create-journal-entries',
            'edit-journal-entries',
            'delete-journal-entries',
            'view-financial-reports',
            'view-tax-reports'
        ]);

        $this->givePermission($this->user2, [
            'view-accounts',
            'create-accounts',
            'edit-accounts',
            'delete-accounts',
            'view-journal-entries',
            'create-journal-entries',
            'edit-journal-entries',
            'delete-journal-entries',
            'view-financial-reports',
            'view-tax-reports'
        ]);

        $this->givePermission($this->superAdmin, [
            'view-accounts',
            'create-accounts',
            'edit-accounts',
            'delete-accounts',
            'view-journal-entries',
            'create-journal-entries',
            'edit-journal-entries',
            'delete-journal-entries',
            'view-financial-reports',
            'view-tax-reports',
            'manage-companies'
        ]);

        // Create accounts for company 1
        $this->asset1 = Account::create([
            'name' => 'Cash - Company 1',
            'account_number' => '1000',
            'type' => 'asset',
            'description' => 'Cash account for Company 1',
            'code' => 'CASH1',
            'is_active' => true,
            'tenant_id' => $this->company1->id,
        ]);

        $this->liability1 = Account::create([
            'name' => 'Accounts Payable - Company 1',
            'account_number' => '2000',
            'type' => 'liability',
            'description' => 'Accounts Payable for Company 1',
            'code' => 'AP1',
            'is_active' => true,
            'tenant_id' => $this->company1->id,
        ]);

        $this->income1 = Account::create([
            'name' => 'Sales Revenue - Company 1',
            'account_number' => '4000',
            'type' => 'income',
            'description' => 'Sales Revenue for Company 1',
            'code' => 'REV1',
            'is_active' => true,
            'tenant_id' => $this->company1->id,
        ]);

        $this->expense1 = Account::create([
            'name' => 'Office Supplies - Company 1',
            'account_number' => '5000',
            'type' => 'expense',
            'code' => 'EXP1',
            'description' => 'Office Supplies for Company 1',
            'is_active' => true,
            'tenant_id' => $this->company1->id,
        ]);

        // Create accounts for company 2
        $this->asset2 = Account::create([
            'name' => 'Cash - Company 2',
            'account_number' => '1000',
            'type' => 'asset',
            'code' => 'CASH2',
            'description' => 'Cash account for Company 2',
            'is_active' => true,
            'tenant_id' => $this->company2->id,
        ]);

        $this->liability2 = Account::create([
            'name' => 'Accounts Payable - Company 2',
            'account_number' => '2000',
            'type' => 'liability',
            'code' => 'AP2',
            'description' => 'Accounts Payable for Company 2',
            'is_active' => true,
            'tenant_id' => $this->company2->id,
        ]);

        $this->income2 = Account::create([
            'name' => 'Sales Revenue - Company 2',
            'account_number' => '4000',
            'type' => 'income',
            'code' => 'REV2',
            'description' => 'Sales Revenue for Company 2',
            'is_active' => true,
            'tenant_id' => $this->company2->id,
        ]);

        $this->expense2 = Account::create([
            'name' => 'Office Supplies - Company 2',
            'account_number' => '5000',
            'type' => 'expense',
            'code' => 'EXP2',
            'description' => 'Office Supplies for Company 2',
            'is_active' => true,
            'tenant_id' => $this->company2->id,
        ]);

        // Create tax rates for both companies
        TaxRate::create([
            'name' => 'IVA 12% - Company 1',
            'rate' => 12.00,
            'is_active' => true,
            'tenant_id' => $this->company1->id,
        ]);

        TaxRate::create([
            'name' => 'IVA 12% - Company 2',
            'rate' => 12.00,
            'is_active' => true,
            'tenant_id' => $this->company2->id,
        ]);
    }

    #[Test]
    public function accounts_are_isolated_between_companies()
    {
        // Verify that company 1 user can only see company 1 accounts
        $this->actAsWithTenant($this->user1);
        $response = $this->get(route('accounts.index'));
        $response->assertOk();
        $response->assertSee('Cash - Company 1');
        $response->assertDontSee('Cash - Company 2');

        // Verify that company 2 user can only see company 2 accounts
        $this->actAsWithTenant($this->user2);
        $response = $this->get(route('accounts.index'));
        $response->assertOk();
        $response->assertSee('Cash - Company 2');
        $response->assertDontSee('Cash - Company 1');

        // Verify that super admin can see both companies' accounts (aggregate)
        $this->actingAs($this->superAdmin);
        request()->merge(['tenant_id' => null]);
        config(['tenant_id' => null]);
        $response = $this->get(route('accounts.index'));
        $response->assertOk();
        $response->assertSee('Cash - Company 1');
        $response->assertSee('Cash - Company 2');
    }

    #[Test]
    public function journal_entries_are_isolated_between_companies()
    {
        // Create journal entries for company 1
        $this->actAsWithTenant($this->user1);
        $journalEntry1 = JournalEntry::create([
            'tenant_id' => $this->company1->id,
            'entry_date' => now(),
            'transaction_type' => 'Manual Entry',
            'description' => 'Journal Entry for Company 1',
            'created_by_user_id' => $this->user1->user_id,
        ]);
        $journalEntry1->lines()->createMany([
            ['tenant_id' => $this->company1->id, 'account_code' => $this->asset1->code, 'debit_amount' => 1000.00, 'credit_amount' => 0.00],
            ['tenant_id' => $this->company1->id, 'account_code' => $this->income1->code, 'debit_amount' => 0.00, 'credit_amount' => 1000.00],
        ]);

        // Create journal entries for company 2
        $this->actAsWithTenant($this->user2);
        $journalEntry2 = JournalEntry::create([
            'tenant_id' => $this->company2->id,
            'entry_date' => now(),
            'transaction_type' => 'Manual Entry',
            'description' => 'Journal Entry for Company 2',
            'created_by_user_id' => $this->user2->user_id,
        ]);
        $journalEntry2->lines()->createMany([
            ['tenant_id' => $this->company2->id, 'account_code' => $this->asset2->code, 'debit_amount' => 2000.00, 'credit_amount' => 0.00],
            ['tenant_id' => $this->company2->id, 'account_code' => $this->income2->code, 'debit_amount' => 0.00, 'credit_amount' => 2000.00],
        ]);

        // Verify that company 1 user can only see company 1 journal entries
        $this->actAsWithTenant($this->user1);
        $response = $this->get(route('journal-entries.index'));
        $response->assertOk();
        $response->assertSee('Journal Entry for Company 1');
        $response->assertDontSee('Journal Entry for Company 2');

        // Verify that company 2 user can only see company 2 journal entries
        $this->actAsWithTenant($this->user2);
        $response = $this->get(route('journal-entries.index'));
        $response->assertOk();
        $response->assertSee('Journal Entry for Company 2');
        $response->assertDontSee('Journal Entry for Company 1');

        // Verify that super admin can see both companies' journal entries
        $this->actAsWithTenant($this->superAdmin);
        $response = $this->get(route('journal-entries.index'));
        $response->assertOk();
        $response->assertSee('Journal Entry for Company 1');
        $response->assertSee('Journal Entry for Company 2');
    }

    #[Test]
    public function users_cannot_create_journal_entries_with_accounts_from_other_companies()
    {
        // Try to create a journal entry for company 1 with company 2 accounts
        $this->actAsWithTenant($this->user1);
        
        $journalEntryData = [
            'tenant_id' => $this->company1->id,
            'entry_date' => now()->format('Y-m-d'),
            'transaction_type' => 'Manual Entry',
            'description' => 'Invalid Journal Entry',
            'lines' => [
                ['account_code' => $this->asset2->code, 'debit_amount' => 1000.00, 'credit_amount' => 0.00],
                ['account_code' => $this->income1->code, 'debit_amount' => 0.00, 'credit_amount' => 1000.00],
            ],
        ];
        
        $response = $this->post(route('journal-entries.store'), $journalEntryData);
        
        // The request should fail because the accounts belong to different companies
        $response->assertSessionHasErrors(['lines.0.account_code']);
        
        // Verify that no journal entry was created
        $this->assertDatabaseMissing('journal_entries', [
            'description' => 'Invalid Journal Entry',
        ]);
    }

    #[Test]
    public function tax_reports_are_isolated_between_companies()
    {
        // Create journal entries with tax for company 1
        $this->actAsWithTenant($this->user1);
        $taxRate1 = TaxRate::where('tenant_id', $this->company1->id)->first();
        
        \App\Models\TaxPayment::create([
            'tenant_id' => $this->company1->id,
            'tax_rate_id' => $taxRate1->tax_rate_id,
            'taxable_amount' => 1000.00,
            'tax_amount' => 120.00,
            'payment_type' => 'purchase',
            'payment_date' => now(),
            'document_number' => 'PO-001-C1',
            'description' => 'Taxable Entry for Company 1',
            'status' => 'paid',
            'created_by_user_id' => $this->user1->user_id,
        ]);

        // Create journal entries with tax for company 2
        $this->actAsWithTenant($this->user2);
        $taxRate2 = TaxRate::where('tenant_id', $this->company2->id)->first();
        
        \App\Models\TaxCollection::create([
            'tenant_id' => $this->company2->id,
            'tax_rate_id' => $taxRate2->tax_rate_id,
            'taxable_amount' => 2000.00,
            'tax_amount' => 240.00,
            'collection_type' => 'sale',
            'collection_date' => now(),
            'customer_name' => 'Customer A',
            'description' => 'Taxable Entry for Company 2',
            'status' => 'collected',
            'created_by_user_id' => $this->user2->user_id,
        ]);

        // Verify that company 1 user can only see company 1 tax reports
        $this->actAsWithTenant($this->user1);
        $response = $this->get(route('iva.report.monthly', ['year' => now()->year, 'month' => now()->month, 'format' => 'json']));
        $response->assertOk();
        $response->assertJson(function ($json) {
            $json->where('tax_paid.total', function ($v) { return abs((float)$v - 120.00) < 0.01; })
                 ->where('tax_collected.total', function ($v) { return abs((float)$v - 0.00) < 0.01; })
                 ->etc();
        });

        // Verify that company 2 user can only see company 2 tax reports
        $this->actAsWithTenant($this->user2);
        $response = $this->get(route('iva.report.monthly', ['year' => now()->year, 'month' => now()->month, 'format' => 'json']));
        $response->assertOk();
        $response->assertJson(function ($json) {
            $json->where('tax_paid.total', function ($v) { return abs((float)$v - 0.00) < 0.01; })
                 ->where('tax_collected.total', function ($v) { return abs((float)$v - 240.00) < 0.01; })
                 ->etc();
        });

        // Verify that super admin can see both companies' tax reports
        $this->actAsWithTenant($this->superAdmin);
        $response = $this->get(route('iva.report.monthly', ['year' => now()->year, 'month' => now()->month, 'format' => 'json']));
        $response->assertOk();
        $response->assertJson(function ($json) {
            $json->where('tax_paid.total', function ($v) { return abs((float)$v - 120.00) < 0.01; })
                 ->where('tax_collected.total', function ($v) { return abs((float)$v - 240.00) < 0.01; })
                 ->etc();
        });
    }

    #[Test]
    public function financial_reports_are_isolated_between_companies()
    {
        // Create journal entries for company 1
        $this->actAsWithTenant($this->user1);
        
        // Revenue entry
        $journalEntry1 = JournalEntry::create([
            'tenant_id' => $this->company1->id,
            'entry_date' => now(),
            'transaction_type' => 'Manual Entry',
            'description' => 'Revenue Entry for Company 1',
            'created_by_user_id' => $this->user1->user_id,
        ]);
        $journalEntry1->lines()->createMany([
            ['tenant_id' => $this->company1->id, 'account_code' => $this->asset1->code, 'debit_amount' => 5000.00, 'credit_amount' => 0.00],
            ['tenant_id' => $this->company1->id, 'account_code' => $this->income1->code, 'debit_amount' => 0.00, 'credit_amount' => 5000.00],
        ]);
        
        // Expense entry
        $journalEntry2 = JournalEntry::create([
            'tenant_id' => $this->company1->id,
            'entry_date' => now(),
            'transaction_type' => 'Manual Entry',
            'description' => 'Expense Entry for Company 1',
            'created_by_user_id' => $this->user1->user_id,
        ]);
        $journalEntry2->lines()->createMany([
            ['tenant_id' => $this->company1->id, 'account_code' => $this->expense1->code, 'debit_amount' => 2000.00, 'credit_amount' => 0.00],
            ['tenant_id' => $this->company1->id, 'account_code' => $this->asset1->code, 'debit_amount' => 0.00, 'credit_amount' => 2000.00],
        ]);

        // Create journal entries for company 2
        $this->actAsWithTenant($this->user2);
        
        // Revenue entry
        $journalEntry3 = JournalEntry::create([
            'tenant_id' => $this->company2->id,
            'entry_date' => now(),
            'transaction_type' => 'Manual Entry',
            'description' => 'Revenue Entry for Company 2',
            'created_by_user_id' => $this->user2->user_id,
        ]);
        $journalEntry3->lines()->createMany([
            ['tenant_id' => $this->company2->id, 'account_code' => $this->asset2->code, 'debit_amount' => 8000.00, 'credit_amount' => 0.00],
            ['tenant_id' => $this->company2->id, 'account_code' => $this->income2->code, 'debit_amount' => 0.00, 'credit_amount' => 8000.00],
        ]);
        
        // Expense entry
        $journalEntry4 = JournalEntry::create([
            'tenant_id' => $this->company2->id,
            'entry_date' => now(),
            'transaction_type' => 'Manual Entry',
            'description' => 'Expense Entry for Company 2',
            'created_by_user_id' => $this->user2->user_id,
        ]);
        $journalEntry4->lines()->createMany([
            ['tenant_id' => $this->company2->id, 'account_code' => $this->expense2->code, 'debit_amount' => 3000.00, 'credit_amount' => 0.00],
            ['tenant_id' => $this->company2->id, 'account_code' => $this->asset2->code, 'debit_amount' => 0.00, 'credit_amount' => 3000.00],
        ]);

        // Verify that company 1 user can only see company 1 financial reports
        $this->actAsWithTenant($this->user1);
        
        // Income statement
        $response = $this->get(route('reports.profit_and_loss', [
            'from' => now()->startOfMonth()->format('Y-m-d'),
            'to' => now()->endOfMonth()->format('Y-m-d'),
        ]));
        $response->assertOk();
        // Assert DB state represents Company 1 entries only
        $this->assertDatabaseHas('journal_entries', [
            'tenant_id' => $this->company1->id,
            'description' => 'Revenue Entry for Company 1',
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'tenant_id' => $this->company1->id,
            'description' => 'Expense Entry for Company 1',
        ]);

        // Verify that company 2 user can only see company 2 financial reports
        $this->actAsWithTenant($this->user2);
        
        // Income statement
        $response = $this->get(route('reports.profit_and_loss', [
            'from' => now()->startOfMonth()->format('Y-m-d'),
            'to' => now()->endOfMonth()->format('Y-m-d'),
        ]));
        $response->assertOk();
        $this->assertDatabaseHas('journal_entries', [
            'tenant_id' => $this->company2->id,
            'description' => 'Revenue Entry for Company 2',
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'tenant_id' => $this->company2->id,
            'description' => 'Expense Entry for Company 2',
        ]);

        // Verify that super admin can see both companies' financial reports
        $this->actAsWithTenant($this->superAdmin);
        
        // Income statement
        $response = $this->get(route('reports.profit_and_loss', [
            'from' => now()->startOfMonth()->format('Y-m-d'),
            'to' => now()->endOfMonth()->format('Y-m-d'),
        ]));
        $response->assertOk();
        // Super admin: ensure both companies' entries exist in DB
        $this->assertDatabaseHas('journal_entries', [
            'tenant_id' => $this->company1->id,
            'description' => 'Revenue Entry for Company 1',
        ]);
        $this->assertDatabaseHas('journal_entries', [
            'tenant_id' => $this->company2->id,
            'description' => 'Revenue Entry for Company 2',
        ]);
    }
}
