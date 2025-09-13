<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CrmUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountsIndexTenantScopeTest extends TestCase
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

        $this->user1 = CrmUser::factory()->create(['tenant_id' => $this->company1->id]);
        $this->user2 = CrmUser::factory()->create(['tenant_id' => $this->company2->id]);

        $this->superAdmin = CrmUser::factory()->create(['tenant_id' => $this->company1->id]);
        $this->superAdmin->forceFill(['is_super_admin' => true])->save();

        // Accounts for company 1
        Account::create([
            'tenant_id' => $this->company1->id,
            'code' => 'C1CASH',
            'name' => 'Cash - Company 1',
            'type' => 'asset',
            'description' => 'Cash',
        ]);
        Account::create([
            'tenant_id' => $this->company1->id,
            'code' => 'C1AP',
            'name' => 'Accounts Payable - Company 1',
            'type' => 'liability',
        ]);

        // Accounts for company 2
        Account::create([
            'tenant_id' => $this->company2->id,
            'code' => 'C2CASH',
            'name' => 'Cash - Company 2',
            'type' => 'asset',
            'description' => 'Cash',
        ]);
        Account::create([
            'tenant_id' => $this->company2->id,
            'code' => 'C2AP',
            'name' => 'Accounts Payable - Company 2',
            'type' => 'liability',
        ]);
    }

    #[Test]
    public function normal_users_see_only_their_tenant_accounts_html()
    {
        $this->actAsWithTenant($this->user1);
        $response = $this->get(route('accounts.index'));
        $response->assertOk();
        $response->assertSee('Cash - Company 1');
        $response->assertDontSee('Cash - Company 2');

        $this->actAsWithTenant($this->user2);
        $response = $this->get(route('accounts.index'));
        $response->assertOk();
        $response->assertSee('Cash - Company 2');
        $response->assertDontSee('Cash - Company 1');
    }

    #[Test]
    public function normal_users_see_only_their_tenant_accounts_json()
    {
        $this->actAsWithTenant($this->user1);
        $response = $this->get(route('accounts.index', ['format' => 'json']));
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonMissing(['name' => 'Cash - Company 2']);

        $this->actAsWithTenant($this->user2);
        $response = $this->get(route('accounts.index', ['format' => 'json']));
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonMissing(['name' => 'Cash - Company 1']);
    }

    #[Test]
    public function super_admin_aggregates_by_default_and_can_filter_by_tenant_json()
    {
        // Aggregate (no tenant_id)
        $this->actingAs($this->superAdmin);
        request()->merge(['tenant_id' => null]);
        config(['tenant_id' => null]);
        $response = $this->get(route('accounts.index', ['format' => 'json']));
        $response->assertOk();
        $response->assertJsonPath('meta.count', 4);

        // Filter by tenant_id
        $response = $this->get(route('accounts.index', ['format' => 'json', 'tenant_id' => $this->company1->id]));
        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['name' => 'Cash - Company 1']);
        $response->assertJsonMissing(['name' => 'Cash - Company 2']);
    }
}
