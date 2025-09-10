<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cuentas principales
        $accounts = [
            // Assets
            ['tenant_id' => 1, 'code' => '1101', 'name' => 'Bank', 'type' => 'Asset', 'description' => 'Main bank account', 'parent_id' => null],
            // Liabilities
            ['tenant_id' => 1, 'code' => '2101', 'name' => 'Accounts Payable', 'type' => 'Liability', 'description' => 'Accounts payable to suppliers', 'parent_id' => null],
            ['tenant_id' => 1, 'code' => '2102', 'name' => 'Accounts Receivable', 'type' => 'Liability', 'description' => 'Accounts receivable from customers', 'parent_id' => null],
            // Income
            ['tenant_id' => 1, 'code' => '3101', 'name' => 'Sales', 'type' => 'Income', 'description' => 'Income from product or service sales', 'parent_id' => null],
            ['tenant_id' => 1, 'code' => '3102', 'name' => 'Other Income', 'type' => 'Income', 'description' => 'Miscellaneous income not related to main sales', 'parent_id' => null],
            // Costs and Expenses
            ['tenant_id' => 1, 'code' => '4101', 'name' => 'Purchases', 'type' => 'Expense', 'description' => 'Merchandise purchases', 'parent_id' => null],
            ['tenant_id' => 1, 'code' => '4102', 'name' => 'Cost of Goods Sold', 'type' => 'Expense', 'description' => 'Direct cost of goods sold', 'parent_id' => null],
            ['tenant_id' => 1, 'code' => '4201', 'name' => 'Administrative Expenses', 'type' => 'Expense', 'description' => 'General administrative expenses', 'parent_id' => null],
            ['tenant_id' => 1, 'code' => '4202', 'name' => 'Sales Expenses', 'type' => 'Expense', 'description' => 'Sales-related expenses', 'parent_id' => null],
            ['tenant_id' => 1, 'code' => '4203', 'name' => 'Financial Expenses', 'type' => 'Expense', 'description' => 'Interest and bank fees', 'parent_id' => null],
            // Taxes
            ['tenant_id' => 1, 'code' => '5101', 'name' => 'VAT Payable (Sales)', 'type' => 'Tax', 'description' => 'VAT generated from sales', 'parent_id' => null],
            ['tenant_id' => 1, 'code' => '5102', 'name' => 'VAT Recoverable (Purchases)', 'type' => 'Tax', 'description' => 'VAT paid on purchases', 'parent_id' => null],
            ['tenant_id' => 1, 'code' => '5103', 'name' => 'Other Taxes', 'type' => 'Tax', 'description' => 'Other taxes and fees', 'parent_id' => null],
            // Equity
            ['tenant_id' => 1, 'code' => '6101', 'name' => 'Share Capital', 'type' => 'Equity', 'description' => 'Shareholder contributions', 'parent_id' => null],
            ['tenant_id' => 1, 'code' => '6102', 'name' => 'Retained Earnings', 'type' => 'Equity', 'description' => 'Accumulated profits or losses', 'parent_id' => null],
        ];

        foreach ($accounts as $data) {
            Account::create($data);
        }
    }
} 