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
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Database\Seeders\AdminUserAndTenantSeeder;

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
        // Create a tenant if none exists
        // Disable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Truncate tables
        \App\Models\Tenant::truncate();
        \App\Models\CrmUser::truncate();
        \App\Models\UserRole::truncate();
        
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create a tenant
        $tenant = \App\Models\Tenant::factory()->create([
            'name' => 'Default Tenant',
            'email' => 'admin@example.com',
        ]);

        // Create an admin user
        $admin = \App\Models\CrmUser::factory()->forTenant($tenant)->create([
            'email' => 'admin@example.com',
        ]);

        // Assign admin role
        $adminRole = \App\Models\UserRole::firstOrCreate([
            'tenant_id' => $tenant->tenant_id,
            'name' => 'admin',
            'description' => 'Administrator',
        ]);

        $admin->roles()->attach($adminRole);
        // 1. Seed foundational data that is mostly static
        $this->call([
            // 
            TenantSeeder::class,
            CrmUserSeeder::class,
            UserRoleSeeder::class,
            //AdminUserAndTenantSeeder::class,
            //PermissionSeeder::class,
            //TaxRateSeeder::class,
            //UserRoleSeeder::class,
            
            // Basic data seeders
            //CrmUserSeeder::class,
            //ProductCategorySeeder::class,
            //ProductFeatureSeeder::class,
            //WarehouseSeeder::class,
            //SupplierSeeder::class,
            //ProductSeeder::class,
            
            // Transactional data seeders
            //OrderSeeder::class,
            //QuotationSeeder::class,
            //PaymentSeeder::class,
            //AccountSeeder::class,
            //TransactionSeeder::class,
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
        $customers = Customer::all();
        if ($customers->isNotEmpty()) {
            Lead::factory(15)->create([
                'customer_id' => $customers->random()->customer_id,
                'assigned_to_user_id' => $users->random()->user_id,
                'created_by_user_id' => $users->random()->user_id,
            ]);
            $this->command->info('Leads created.');
        }

        // Create integration data (lead -> customer -> opportunity -> invoice -> payments)
        if (class_exists(LeadToClientIntegrationSeeder::class)) {
            $this->call(LeadToClientIntegrationSeeder::class);
            $this->command->info('Integration data created.');
        }

        // Call the refactored seeders with proper error handling
        $seeders = [
            PurchaseOrderSeeder::class,
            UnitPriceCalculationSeeder::class => 'Unit price calculation data created.',
            PurchaseOrderStatusFlowSeeder::class => 'Purchase order status flow demo created.',
            ReportDataSeeder::class => 'Report data created.'
        ];

        foreach ($seeders as $seeder => $message) {
            if (is_int($seeder)) {
                $seeder = $message;
                $message = null;
            }
            
            if (class_exists($seeder)) {
                $this->call($seeder);
                if ($message) {
                    $this->command->info($message);
                }
            }
        }
       
        // The OrderSeeder and InvoiceSeeder depend on the above, so they should be called after.
        // We will refactor them next.
    }
}