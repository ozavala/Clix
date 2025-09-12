<?php

namespace Tests\Feature;

use App\Models\Account;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    
    #[Test]
    public function required_accounts_exist()
    {
        // Run the AccountSeeder
        $this->seed(\Database\Seeders\AccountSeeder::class);

        // Check that required accounts exist
        $requiredAccounts = [
            '1101' => 'Bank',
            '2101' => 'Accounts Payable',
            '2102' => 'Accounts Receivable',
        ];

        foreach ($requiredAccounts as $code => $name) {
            $account = Account::where('code', $code)->first();
            $this->assertNotNull($account, "Account with code {$code} ({$name}) does not exist");
            $this->assertEquals($name, $account->name, "Account with code {$code} has incorrect name");
        }
    }
}
