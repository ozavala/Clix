<?php

namespace Tests\Feature;

use App\Models\Account;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we have a clean state for each test
        Account::query()->delete();
    }

    #[Test]
    public function required_accounts_exist()
    {
        // Run the AccountSeeder - it will use the tenant from TestCase
        $this->seed(\Database\Seeders\AccountSeeder::class);

        // Check that required accounts exist for the current tenant
        $requiredAccounts = [
            '1101' => 'Bank',
            '2101' => 'Accounts Payable',
            '2102' => 'Accounts Receivable',
        ];

        foreach ($requiredAccounts as $code => $name) {
            // Use the tenant from the test case
            $account = Account::where('code', $code)
                ->where('tenant_id', $this->tenant->id)
                ->first();
                
            $this->assertNotNull($account, "Account with code {$code} ({$name}) does not exist for tenant {$this->tenant->id}");
            $this->assertEquals($name, $account->name, "Account with code {$code} has incorrect name");
            $this->assertEquals($this->tenant->id, $account->tenant_id, "Account with code {$code} has incorrect tenant_id");
        }
    }
}
