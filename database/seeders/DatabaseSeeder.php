<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Contact;
use App\Models\CrmUser;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quotation;
use Illuminate\Database\Seeder;
use Database\Seeders\CrmUserSeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\ProductFeatureSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserRoleSeeder;
use Database\Seeders\WarehouseSeeder;
use Database\Seeders\SupplierSeeder;
use App\Models\Supplier;
use Database\Seeders\TaxRateSeeder;
use Database\Seeders\AccountSeeder;
use Database\Seeders\TenantSeeder;
use Database\Seeders\TransactionSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // For a clean slate, you might want to disable foreign key checks before truncating
        // \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // CrmUser::truncate(); // etc.
        // \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Seed foundational data that is mostly static
        $this->call([
            PermissionSeeder::class,
            TaxRateSeeder::class,
            SettingsTableSeeder::class,
            UserRoleSeeder::class,
            CrmUserSeeder::class, // Creates specific users like 'Admin', 'Sales'
            ProductCategorySeeder::class,
            ProductFeatureSeeder::class,
            WarehouseSeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class, // This seeder creates specific products, which is fine
            QuotationSeeder::class,
            OrderSeeder::class,
            PaymentSeeder::class,
            AccountSeeder::class,
            TenantSeeder::class, // <-- Agregado
            TransactionSeeder::class,  // <-- Agregado
        ]);

        // 2. Use factories to create a rich, dynamic dataset for testing
        $this->command->info('Creating dynamic data using factories...');

        // Get all users (from seeder and potentially new ones) to assign tasks/opportunities
        $users = CrmUser::all();
        if ($users->count() < 10) {
            $users = $users->merge(CrmUser::factory(10 - $users->count())->create());
        }

        // Create customers, each with contacts and opportunities
        Customer::factory(25)
            ->has(Contact::factory()->count(rand(1, 3)), 'contacts')
            ->create(['created_by_user_id' => $users->random()->user_id])
            ->each(function ($customer) use ($users) {
                // For each customer, create some opportunities to populate the Kanban board
                if ($customer->contacts->isNotEmpty()) {
                    Opportunity::factory(rand(1, 4))->create([
                        'customer_id' => $customer->customer_id,
                        'contact_id' => $customer->contacts->random()->contact_id,
                        'assigned_to_user_id' => $users->random()->user_id,
                        'created_by_user_id' => $users->random()->user_id,
                    ]);
                }
            });
        $this->command->info('Customers, Contacts, and Opportunities created.');

        // To properly test the polymorphic relationship, create contacts for Suppliers too.
        // This assumes a SupplierFactory exists and the Supplier model has a 'contacts' relationship.
        $suppliers = Supplier::all();
        if ($suppliers->isNotEmpty()) {
            // Get a few suppliers and add contacts to them
            $suppliers->random(min(3, $suppliers->count()))->each(function ($supplier) {
                $supplier->contacts()->saveMany(Contact::factory(rand(1, 2))->make());
            });
            $this->command->info('Contacts for Suppliers created.');
        }

        // Create some standalone leads that haven't been converted yet
        Lead::factory(15)->create([
            'customer_id' => Customer::all()->random()->customer_id,
            'assigned_to_user_id' => $users->random()->user_id,
            'created_by_user_id' => $users->random()->user_id,
        ]);
        $this->command->info('Leads created.');

        // Create integration data (lead -> customer -> opportunity -> invoice -> payments)
        $this->call(LeadToClientIntegrationSeeder::class);
        $this->command->info('Integration data created.');

        // Call the newly refactored seeders
        $this->call(PurchaseOrderSeeder::class);
        $this->call(QuotationSeeder::class);
       
        // Create unit price calculation data
        $this->call(UnitPriceCalculationSeeder::class);
        $this->command->info('Unit price calculation data created.');

        // Create purchase order status flow demo
        $this->call(PurchaseOrderStatusFlowSeeder::class);
        $this->command->info('Purchase order status flow demo created.');
        
        // Create comprehensive report data
        $this->call(ReportDataSeeder::class);
        $this->command->info('Report data created.');
       
        // The OrderSeeder and InvoiceSeeder depend on the above, so they should be called after.
        // We will refactor them next.
    }
}