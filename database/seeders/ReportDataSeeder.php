<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\CrmUser;
use App\Models\UserRole;
use App\Models\Warehouse;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Quotation;
use App\Models\Tenant;
use App\Models\QuotationItem;
use App\Models\Lead;
use App\Models\Opportunity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Seeding report data...');

        // Crear roles
        $this->createRoles();
        
        // Crear usuarios
        $this->createCrmUsers();
        
        // Crear categorías de productos
        $this->createProductCategories();
        
        // Crear productos
        $this->createProducts();
        
        // Crear warehouses
        $this->createWarehouses();
        
        // Crear clientes
        $this->createCustomers();
        
        // Crear proveedores
        $this->createSuppliers();
        
        // Crear leads y oportunidades
        $this->createLeadsAndOpportunities();
        
        // Crear cotizaciones
        $this->createQuotations();
        
        // Crear órdenes de compra
        $this->createPurchaseOrders();
        
        // Crear órdenes de venta
        $this->createOrders();
        
        // Crear facturas
        $this->createInvoices();
        
        // Crear pagos
        $this->createPayments();
        
        // Asignar stock a productos
        $this->assignProductStock();
        
        $this->command->info('✅ Report data seeded successfully!');
    }

    private function createRoles(): void
    {
        $roles = [
            ['name' => 'admin', 'description' => 'Administrator'],
            ['name' => 'sales', 'description' => 'Sales Representative'],
            ['name' => 'manager', 'description' => 'Sales Manager'],
            ['name' => 'accountant', 'description' => 'Accountant'],
        ];

        foreach ($roles as $role) {
            UserRole::firstOrCreate(['name' => $role['name']], $role);
        }
    }

    private function createCrmUsers(): void
    {
        $users = [
            [
                'tenant_id' => 1,
                'username' => 'john.smith',
                'full_name' => 'John Smith',
                'email' => 'john.smith@company.com',
                'role' => 'admin'
            ],
            [
                'tenant_id' => 1,
                'username' => 'maria.garcia',
                'full_name' => 'Maria Garcia',
                'email' => 'maria.garcia@company.com',
                'role' => 'sales'
            ],
            [
                'tenant_id' => 1,
                'username' => 'david.johnson',
                'full_name' => 'David Johnson',
                'email' => 'david.johnson@company.com',
                'role' => 'sales'
            ],
            [
                'tenant_id' => 1,
                'username' => 'sarah.wilson',
                'full_name' => 'Sarah Wilson',
                'email' => 'sarah.wilson@company.com',
                'role' => 'manager'
            ],
            [
                'tenant_id' => 1,
                'username' => 'michael.brown',
                'full_name' => 'Michael Brown',
                'email' => 'michael.brown@company.com',
                'role' => 'accountant'
            ],
        ];

        foreach ($users as $userData) {
            // Create or get a tenant first
            $tenant = Tenant::firstOrCreate(
                ['name' => $userData['full_name'] . ' Tenant'],
                [
                    'legal_id' => 'TENANT-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $userData['username']), 0, 8)) . '-' . rand(1000, 9999),
                    'is_active' => true
                ]
            );

            $user = CrmUser::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'tenant_id' => $tenant->id,
                    'username' => $userData['username'],
                    'full_name' => $userData['full_name'],
                    'email' => $userData['email'],
                    'password' => bcrypt('password123'),
                ]
            );

            // Asignar rol directamente sin verificar duplicados
            $role = UserRole::where('name', $userData['role'])->first();
            if ($role) {
                DB::table('crm_user_user_role')->insertOrIgnore([
                    'crm_user_id' => $user->user_id,
                    'role_id' => $role->role_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function createProductCategories(): void
    {
        $categories = [
            ['name' => 'Electronics', 'description' => 'Electronic devices and accessories'],
            ['name' => 'Clothing', 'description' => 'Apparel and fashion items'],
            ['name' => 'Home & Garden', 'description' => 'Home improvement and garden products'],
            ['name' => 'Sports & Outdoors', 'description' => 'Sports equipment and outdoor gear'],
            ['name' => 'Books & Media', 'description' => 'Books, movies, and media'],
            ['name' => 'Automotive', 'description' => 'Automotive parts and accessories'],
            ['name' => 'Health & Beauty', 'description' => 'Health and beauty products'],
            ['name' => 'Toys & Games', 'description' => 'Toys and entertainment products'],
        ];

        // Get the first tenant or create one if none exists
        $tenant = Tenant::first();
        if (!$tenant) {
            $tenant = Tenant::create([
                'name' => 'Default Tenant',
                'legal_id' => 'TENANT-DEFAULT-' . rand(1000, 9999),
                'is_active' => true
            ]);
        }

        foreach ($categories as $category) {
            ProductCategory::firstOrCreate(
                ['name' => $category['name'], 'tenant_id' => $tenant->id],
                array_merge($category, ['tenant_id' => $tenant->id])
            );
        }
    }

    private function createProducts(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            $tenant = Tenant::create([
                'name' => 'Default Tenant',
                'legal_id' => 'TENANT-DEFAULT-' . rand(1000, 9999),
                'is_active' => true
            ]);
        }
        
        $products = [
            // Electronics
            ['name' => 'iPhone 15 Pro', 'sku' => 'IPH15PRO', 'price' => 999.99, 'cost' => 650.00, 'category' => 'Electronics'],
            ['name' => 'Samsung Galaxy S24', 'sku' => 'SAMS24', 'price' => 899.99, 'cost' => 580.00, 'category' => 'Electronics'],
            ['name' => 'MacBook Air M2', 'sku' => 'MBAIRM2', 'price' => 1199.99, 'cost' => 800.00, 'category' => 'Electronics'],
            ['name' => 'Dell XPS 13', 'sku' => 'DELLXPS13', 'price' => 1099.99, 'cost' => 720.00, 'category' => 'Electronics'],
            ['name' => 'AirPods Pro', 'sku' => 'AIRPODSPRO', 'price' => 249.99, 'cost' => 150.00, 'category' => 'Electronics'],
            
            // Clothing
            ['name' => 'Nike Air Max', 'sku' => 'NIKEAIRMAX', 'price' => 129.99, 'cost' => 65.00, 'category' => 'Clothing'],
            ['name' => 'Adidas Ultraboost', 'sku' => 'ADIDASUB', 'price' => 179.99, 'cost' => 90.00, 'category' => 'Clothing'],
            ['name' => 'Levi\'s 501 Jeans', 'sku' => 'LEVIS501', 'price' => 89.99, 'cost' => 45.00, 'category' => 'Clothing'],
            ['name' => 'H&M T-Shirt', 'sku' => 'HMTEE', 'price' => 19.99, 'cost' => 8.00, 'category' => 'Clothing'],
            ['name' => 'Zara Blazer', 'sku' => 'ZARABLAZER', 'price' => 159.99, 'cost' => 80.00, 'category' => 'Clothing'],
            
            // Home & Garden
            ['name' => 'IKEA Desk', 'sku' => 'IKEADESK', 'price' => 199.99, 'cost' => 120.00, 'category' => 'Home & Garden'],
            ['name' => 'Garden Hose', 'sku' => 'GARDENHOSE', 'price' => 39.99, 'cost' => 20.00, 'category' => 'Home & Garden'],
            ['name' => 'LED Light Bulbs', 'sku' => 'LEDBULBS', 'price' => 24.99, 'cost' => 12.00, 'category' => 'Home & Garden'],
            ['name' => 'Kitchen Mixer', 'sku' => 'KITCHMIXER', 'price' => 89.99, 'cost' => 45.00, 'category' => 'Home & Garden'],
            ['name' => 'Coffee Maker', 'sku' => 'COFFEEMAKER', 'price' => 149.99, 'cost' => 75.00, 'category' => 'Home & Garden'],
            
            // Sports & Outdoors
            ['name' => 'Yoga Mat', 'sku' => 'YOGAMAT', 'price' => 29.99, 'cost' => 15.00, 'category' => 'Sports & Outdoors'],
            ['name' => 'Tennis Racket', 'sku' => 'TENNISRACKET', 'price' => 79.99, 'cost' => 40.00, 'category' => 'Sports & Outdoors'],
            ['name' => 'Camping Tent', 'sku' => 'CAMPTENT', 'price' => 199.99, 'cost' => 100.00, 'category' => 'Sports & Outdoors'],
            ['name' => 'Bicycle Helmet', 'sku' => 'BIKEHELMET', 'price' => 59.99, 'cost' => 30.00, 'category' => 'Sports & Outdoors'],
            ['name' => 'Fitness Tracker', 'sku' => 'FITTRACKER', 'price' => 99.99, 'cost' => 50.00, 'category' => 'Sports & Outdoors'],
            
            // Books & Media
            ['name' => 'Harry Potter Set', 'sku' => 'HARRYPOTTER', 'price' => 89.99, 'cost' => 45.00, 'category' => 'Books & Media'],
            ['name' => 'Bluetooth Speaker', 'sku' => 'BTSPEAKER', 'price' => 79.99, 'cost' => 40.00, 'category' => 'Books & Media'],
            ['name' => 'Kindle Paperwhite', 'sku' => 'KINDLEPW', 'price' => 139.99, 'cost' => 70.00, 'category' => 'Books & Media'],
            ['name' => 'Gaming Headset', 'sku' => 'GAMEHEADSET', 'price' => 119.99, 'cost' => 60.00, 'category' => 'Books & Media'],
            ['name' => 'Board Game Collection', 'sku' => 'BOARDGAMES', 'price' => 49.99, 'cost' => 25.00, 'category' => 'Books & Media'],
        ];

        foreach ($products as $product) {
            $category = ProductCategory::where('name', $product['category'])
                                    ->where('tenant_id', $tenant->id)
                                    ->first();
           // dd($category);
            if ($category) {
                Product::firstOrCreate(
                    ['sku' => $product['sku'], 'tenant_id' => $tenant->id],
                    [
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'cost' => $product['cost'],
                        'category_id' => $category->id,
                        'tenant_id' => $tenant->id,
                    ]
                );
            }
        }
    }

    private function createWarehouses(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            $tenant = Tenant::create([
                'name' => 'Default Tenant',
                'legal_id' => 'TENANT-DEFAULT-' . rand(1000, 9999),
                'is_active' => true
            ]);
        }

        $warehouses = [
            [
                'name' => 'Main Warehouse',
                'addressable_type' => 'App\\Models\\Warehouse',
                'addressable_id' => 1,
                'is_active' => true
            ],
            [
                'name' => 'East Coast Distribution',
                'addressable_type' => 'App\\Models\\Warehouse',
                'addressable_id' => 2,
                'is_active' => true
            ],
            [
                'name' => 'West Coast Distribution',
                'addressable_type' => 'App\\Models\\Warehouse',
                'addressable_id' => 3,
                'is_active' => true
            ],
        ];

        foreach ($warehouses as $warehouse) {
            // First create the warehouse
            $warehouseModel = Warehouse::firstOrCreate(
                ['name' => $warehouse['name'], 'tenant_id' => $tenant->id],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $warehouse['name'],
                    'is_active' => $warehouse['is_active'],
                    'addressable_type' => 'App\\Models\\Warehouse',
                    'addressable_id' => $warehouse['addressable_id']
                ]
            );

            // Then create the address
            $warehouseModel->addresses()->create([
                'street_address_line_1' => '123 ' . $warehouse['name'] . ' St',
                'city' => 'Some City',
                'state_province' => 'CA',
                'postal_code' => '12345',
                'country_code' => 'US',
                'address_type' => 'warehouse',
                'is_primary' => true,
                'tenant_id' => $tenant->id
            ]);
        }
    }

    private function createCustomers(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            $tenant = Tenant::create([
                'name' => 'Default Tenant',
                'legal_id' => 'TENANT-DEFAULT-' . rand(1000, 9999),
                'is_active' => true
            ]);
        }

        $customers = [
            // Empresas
            [
                'type' => 'Company',
                'company_name' => 'Tech Solutions Inc',
                'email' => 'contact@techsolutions.com',
                'phone_number' => '+1-555-0101',
                'legal_id' => 'TECH001',
                'address_street' => '123 Tech Street',
                'address_city' => 'San Francisco',
                'tenant_id' => $tenant->id,
                'address_state' => 'CA',
                'address_postal_code' => '94105',
                'address_country' => 'USA'
            ],
            [
                'type' => 'Company',
                'company_name' => 'Fashion Forward LLC',
                'email' => 'info@fashionforward.com',
                'phone_number' => '+1-555-0102',
                'legal_id' => 'FASH002',
                'tenant_id' => $tenant->id,
                'address_street' => '456 Fashion Ave',
                'address_city' => 'New York',
                'address_state' => 'NY',
                'address_postal_code' => '10001',
                'address_country' => 'USA'
            ],
            [
                'type' => 'Company',
                'company_name' => 'Home Improvement Co',
                'email' => 'sales@homeimprovement.com',
                'phone_number' => '+1-555-0103',
                'legal_id' => 'HOME003',
                'address_street' => '789 Home Blvd',
                'address_city' => 'Chicago',
                'address_state' => 'IL',
                'address_postal_code' => '60601',
                'address_country' => 'USA'
            ],
            [
                'type' => 'Company',
                'company_name' => 'Sports Equipment Ltd',
                'email' => 'orders@sportsequipment.com',
                'phone_number' => '+1-555-0104',
                'legal_id' => 'SPORT004',
                'address_street' => '321 Sports Way',
                'address_city' => 'Miami',
                'address_state' => 'FL',
                'address_postal_code' => '33101',
                'address_country' => 'USA'
            ],
            [
                'type' => 'Company',
                'company_name' => 'Media Store',
                'email' => 'customerservice@mediastore.com',
                'phone_number' => '+1-555-0105',
                'legal_id' => 'MEDIA005',
                'address_street' => '654 Media Lane',
                'address_city' => 'Los Angeles',
                'address_state' => 'CA',
                'address_postal_code' => '90001',
                'address_country' => 'USA'
            ],
            
            // Personas
            [
                'type' => 'Person',
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@email.com',
                'phone_number' => '+1-555-0201',
                'legal_id' => 'PERSON001',
                'address_street' => '123 Personal St',
                'address_city' => 'Boston',
                'address_state' => 'MA',
                'address_postal_code' => '02101',
                'address_country' => 'USA'
            ],
            [
                'type' => 'Person',
                'first_name' => 'Maria',
                'last_name' => 'Garcia',
                'email' => 'maria.garcia@email.com',
                'phone_number' => '+1-555-0202',
                'legal_id' => 'PERSON002',
                'address_street' => '456 Personal Ave',
                'address_city' => 'Austin',
                'address_state' => 'TX',
                'address_postal_code' => '73301',
                'address_country' => 'USA'
            ],
            [
                'type' => 'Person',
                'first_name' => 'David',
                'last_name' => 'Johnson',
                'email' => 'david.johnson@email.com',
                'phone_number' => '+1-555-0203',
                'legal_id' => 'PERSON003',
                'address_street' => '789 Personal Blvd',
                'address_city' => 'Seattle',
                'address_state' => 'WA',
                'address_postal_code' => '98101',
                'address_country' => 'USA'
            ],
            [
                'type' => 'Person',
                'first_name' => 'Sarah',
                'last_name' => 'Wilson',
                'email' => 'sarah.wilson@email.com',
                'phone_number' => '+1-555-0204',
                'legal_id' => 'PERSON004',
                'address_street' => '321 Personal Way',
                'address_city' => 'Denver',
                'address_state' => 'CO',
                'address_postal_code' => '80201',
                'address_country' => 'USA'
            ],
            [
                'type' => 'Person',
                'first_name' => 'Michael',
                'last_name' => 'Brown',
                'email' => 'michael.brown@email.com',
                'phone_number' => '+1-555-0205',
                'legal_id' => 'PERSON005',
                'address_street' => '654 Personal Lane',
                'address_city' => 'Portland',
                'address_state' => 'OR',
                'address_postal_code' => '97201',
                'address_country' => 'USA'
            ],
        ];

        foreach ($customers as $customerData) {
            // Ensure tenant_id is set for all customers
            $customerData['tenant_id'] = $tenant->id;
            $customer = Customer::firstOrCreate(
                ['email' => $customerData['email'], 'tenant_id' => $tenant->id],
                $customerData
            );
        }
    }

    private function createSuppliers(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            $tenant = Tenant::create([
                'name' => 'Default Tenant',
                'legal_id' => 'TENANT-DEFAULT-' . rand(1000, 9999),
                'is_active' => true
            ]);
        }

        $suppliers = [
            [
                'name' => 'Apple Inc', 
                'legal_id' => 'SUP-3001', 
                'email' => 'supplier@apple.com', 
                'phone_number' => '+1-555-0201',
                'noteable_type' => 'App\\Models\\Supplier',
                'noteable_id' => 1
            ],
            [
                'name' => 'Samsung Electronics', 
                'legal_id' => 'SUP-3002', 
                'email' => 'orders@samsung.com', 
                'phone_number' => '+1-555-0202',
                'noteable_type' => 'App\\Models\\Supplier',
                'noteable_id' => 2
            ],
            [
                'name' => 'Nike Inc', 
                'legal_id' => 'SUP-3003', 
                'email' => 'supplier@nike.com', 
                'phone_number' => '+1-555-0203',
                'noteable_type' => 'App\\Models\\Supplier',
                'noteable_id' => 3
            ],
            [
                'name' => 'Adidas AG', 
                'legal_id' => 'SUP-3004', 
                'email' => 'orders@adidas.com', 
                'phone_number' => '+1-555-0204',
                'noteable_type' => 'App\\Models\\Supplier',
                'noteable_id' => 4
            ],
            [
                'name' => 'IKEA Group', 
                'legal_id' => 'SUP-3005', 
                'email' => 'supplier@ikea.com', 
                'phone_number' => '+1-555-0205',
                'noteable_type' => 'App\\Models\\Supplier',
                'noteable_id' => 5
            ],
            [
                'name' => 'Dell Technologies', 
                'legal_id' => 'SUP-3006', 
                'email' => 'orders@dell.com', 
                'phone_number' => '+1-555-0206',
                'noteable_type' => 'App\\Models\\Supplier',
                'noteable_id' => 6
            ],
            [
                'name' => 'Sony Corporation', 
                'legal_id' => 'SUP-3007', 
                'email' => 'supplier@sony.com', 
                'phone_number' => '+1-555-0207',
                'noteable_type' => 'App\\Models\\Supplier',
                'noteable_id' => 7
            ],
            [
                'name' => 'LG Electronics', 
                'legal_id' => 'SUP-3008', 
                'email' => 'orders@lg.com', 
                'phone_number' => '+1-555-0208',
                'noteable_type' => 'App\\Models\\Supplier',
                'noteable_id' => 8
            ],
        ];

        foreach ($suppliers as $supplier) {
            // Ensure tenant_id is set for all suppliers
            $supplier['tenant_id'] = $tenant->id;
            Supplier::firstOrCreate(
                ['email' => $supplier['email'], 'tenant_id' => $tenant->id],
                $supplier
            );
            }
        }

    private function createLeadsAndOpportunities(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            $tenant = Tenant::create([
                'name' => 'Default Tenant',
                'legal_id' => 'TENANT-DEFAULT-' . rand(1000, 9999),
                'is_active' => true
            ]);
        }

        $leads = [
            [
                'title' => 'New Tech Startup',
                'contact_name' => 'John Startup',
                'contact_email' => 'contact@newtechstartup.com',
                'contact_phone' => '+1-555-0301',
                'description' => 'New technology startup looking for software solutions',
                'tenant_id' => $tenant->id,
                'value' => 50000,
                'status' => 'New',
                'source' => 'Website'
            ],
            [
                'title' => 'Fashion Boutique',
                'contact_name' => 'Maria Fashion',
                'contact_email' => 'info@fashionboutique.com',
                'contact_phone' => '+1-555-0302',
                'description' => 'Fashion boutique expanding their online presence',
                'tenant_id' => $tenant->id,
                'value' => 25000,
                'status' => 'New',
                'source' => 'Referral'
            ],
            [
                'title' => 'Gym Equipment Store',
                'contact_name' => 'David Fitness',
                'contact_email' => 'sales@gymequipment.com',
                'contact_phone' => '+1-555-0303',
                'description' => 'Gym equipment store needs inventory management system',
                'value' => 35000,
                'status' => 'New',
                'source' => 'Website'
            ],
            [
                'title' => 'Online Bookstore',
                'contact_name' => 'Sarah Books',
                'contact_email' => 'orders@onlinebookstore.com',
                'contact_phone' => '+1-555-0304',
                'description' => 'Online bookstore looking for e-commerce solution',
                'value' => 40000,
                'status' => 'New',
                'source' => 'Social Media'
            ],
            [
                'title' => 'Auto Repair Shop',
                'contact_name' => 'Mike Mechanic',
                'contact_email' => 'service@autorepair.com',
                'contact_phone' => '+1-555-0305',
                'description' => 'Auto repair shop needs customer management system',
                'value' => 30000,
                'status' => 'New',
                'source' => 'Referral'
            ],
        ];

        foreach ($leads as $leadData) {
            // Ensure tenant_id is set for all leads
            $leadData['tenant_id'] = $tenant->id;
            $lead = Lead::firstOrCreate(
                ['contact_email' => $leadData['contact_email']],
                $leadData
            );

            // Crear oportunidad para algunos leads
            if (rand(1, 3) === 1) {
                $opportunityData = [
                    'name' => $lead->title . ' - Opportunity',
                    'description' => 'Opportunity created from lead: ' . $lead->description,
                    'amount' => $lead->value * (rand(90, 120) / 100), // Random value ±10%
                    'expected_close_date' => now()->addDays(rand(30, 90)),
                    'stage' => 'Proposal',
                    'probability' => rand(30, 70),
                    'lead_id' => $lead->lead_id,
                    'tenant_id' => $tenant->id,
                    'created_by_user_id' => CrmUser::where('tenant_id', $tenant->id)->first()->user_id ?? null
                ];

                Opportunity::firstOrCreate(
                    ['lead_id' => $lead->lead_id, 'tenant_id' => $tenant->id],
                    $opportunityData
                );
            }
        }
    }

    private function createQuotations(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            $tenant = Tenant::create([
                'name' => 'Default Tenant',
                'legal_id' => 'TENANT-DEFAULT-' . rand(1000, 9999),
                'is_active' => true
            ]);
        }

        $opportunities = Opportunity::where('tenant_id', $tenant->id)->get();
        $products = Product::where('tenant_id', $tenant->id)->get();
        $users = CrmUser::where('tenant_id', $tenant->id)
                      ->whereHas('roles', function ($query) {
            $query->whereIn('name', ['sales', 'manager']);
        })->get();

        for ($i = 0; $i < 15; $i++) {
            $opportunity = $opportunities->random();
            $user = $users->random();
            
            $quotation = Quotation::create([
                'opportunity_id' => $opportunity->opportunity_id,
                'subject' => 'Quotation for ' . $opportunity->name,
                'quotation_number' => 'QUT-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $opportunity->name), 0, 8)) . '-' . rand(1000, 9999),
                'tenant_id' => $tenant->id,
                'quotation_date' => now()->subDays(rand(1, 30)),
                'expiry_date' => now()->addDays(rand(7, 30)),
                'status' => ['Draft', 'Sent', 'Accepted', 'Rejected'][rand(0, 3)],
                'subtotal' => 0,
                'tax_percentage' => 8.5,
                'tax_amount' => 0,
                'discount_type' => 'percentage',
                'discount_value' => rand(0, 15),
                'discount_amount' => 0,
                'total_amount' => 0,
                'created_by_user_id' => $user->user_id,
            ]);

            // Crear items de cotización
            $numItems = rand(1, 5);
            $subtotal = 0;
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(1, 10);
                $unitPrice = $product->price * (1 - rand(5, 20) / 100); // Descuento del 5-20%
                $itemTotal = $quantity * $unitPrice;
                $subtotal += $itemTotal;

                QuotationItem::create([
                    'quotation_id' => $quotation->quotation_id,
                    'product_id' => $product->product_id,
                    'item_name' => $product->name,
                    'item_description' => $product->description ?? 'Product description',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'item_total' => $itemTotal,
                ]);
            }

            // Actualizar totales
            $discountAmount = $subtotal * ($quotation->discount_value / 100);
            $taxAmount = ($subtotal - $discountAmount) * ($quotation->tax_percentage / 100);
            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            $quotation->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);
        }
    }

    private function createPurchaseOrders(): void
    {
        $suppliers = Supplier::all();
        $products = Product::all();
        $users = CrmUser::all();

        for ($i = 0; $i < 20; $i++) {
            $supplier = $suppliers->random();
            $user = $users->random();
            
            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $supplier->supplier_id,
                'purchase_order_number' => 'PO-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'order_date' => now()->subDays(rand(1, 60)),
                'expected_delivery_date' => now()->addDays(rand(7, 30)),
                'status' => ['Draft', 'Confirmed', 'Dispatched', 'Partially Received', 'Fully Received', 'Paid'][rand(0, 5)],
                'subtotal' => 0,
                'tax_percentage' => 8.5,
                'tax_amount' => 0,
                'shipping_cost' => rand(50, 200),
                'total_amount' => 0,
                'created_by_user_id' => $user->user_id,
                'tenant_id' => $user->tenant_id, // Add tenant_id from the user
            ]);

            // Crear items de orden de compra
            $numItems = rand(2, 8);
            $subtotal = 0;
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(10, 100);
                $unitPrice = $product->cost * (1 + rand(5, 25) / 100); // Margen del 5-25%
                $itemTotal = $quantity * $unitPrice;
                $subtotal += $itemTotal;

                PurchaseOrderItem::create([
                    'tenant_id' => $purchaseOrder->tenant_id,
                    'purchase_order_id' => $purchaseOrder->purchase_order_id,
                    'product_id' => $product->product_id,
                    'item_name' => $product->name,
                    'item_description' => $product->description ?? 'Product description',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'item_total' => $itemTotal,
                ]);
            }

            // Actualizar totales
            $taxAmount = $subtotal * ($purchaseOrder->tax_percentage / 100);
            $totalAmount = $subtotal + $taxAmount + $purchaseOrder->shipping_cost;

            $purchaseOrder->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);
        }
    }

    private function createOrders(): void
    {
        $customers = Customer::all();
        $products = Product::all();
        $users = CrmUser::whereHas('roles', function ($query) {
            $query->whereIn('name', ['sales', 'manager']);
        })->get();

        for ($i = 0; $i < 50; $i++) {
            $customer = $customers->random();
            $user = $users->random();
            
            $order = Order::create([
                'customer_id' => $customer->customer_id,
                'order_number' => 'ORD-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'order_date' => now()->subDays(rand(1, 90)),
                'status' => ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'][rand(0, 4)],
                'subtotal' => 0,
                'tax_percentage' => 8.5,
                'tax_amount' => 0,
                'shipping_cost' => rand(20, 100),
                'total_amount' => 0,
                'created_by_user_id' => $user->user_id,
                'tenant_id' => $user->tenant_id, // Add tenant_id from the user
            ]);

            // Crear items de orden
            $numItems = rand(1, 5);
            $subtotal = 0;

            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(1, 10);
                $unitPrice = $product->price;
                $itemTotal = $quantity * $unitPrice;
                $subtotal += $itemTotal;

                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $product->product_id,
                    'item_name' => $product->name,
                    'item_description' => $product->description ?? 'Product description',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'item_total' => $itemTotal,
                ]);
            }

            // Actualizar totales
            $taxAmount = $subtotal * ($order->tax_percentage / 100);
            $totalAmount = $subtotal + $taxAmount + $order->shipping_cost;

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);
        }
    }

    private function createInvoices(): void
    {
        $orders = Order::whereIn('status', ['Delivered', 'Shipped'])->get();
        $customers = Customer::all();
        $products = Product::all();
        $users = CrmUser::all();

        // Crear facturas basadas en órdenes
        foreach ($orders as $order) {
            $user = $users->random();
            $invoice = Invoice::create([
                'customer_id' => $order->customer_id,
                'order_id' => $order->order_id,
                'invoice_number' => 'INV-' . str_pad($order->order_id, 4, '0', STR_PAD_LEFT),
                'invoice_date' => $order->order_date->addDays(rand(1, 7)),
                'due_date' => $order->order_date->addDays(rand(15, 30)),
                'status' => ['Draft', 'Sent', 'Partially Paid', 'Paid', 'Overdue'][rand(0, 4)],
                'subtotal' => $order->subtotal,
                'tax_percentage' => $order->tax_percentage,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
                'created_by_user_id' => $user->user_id,
                'tenant_id' => $user->tenant_id, // Add tenant_id from the user
            ]);

            // Crear items de factura basados en items de orden
            foreach ($order->items as $orderItem) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->invoice_id,
                    'product_id' => $orderItem->product_id,
                    'item_name' => $orderItem->item_name,
                    'item_description' => $orderItem->item_description,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'item_total' => $orderItem->item_total,
                ]);
            }
        }

        // Crear algunas facturas adicionales sin orden
        for ($i = 0; $i < 10; $i++) {
            $customer = $customers->random();
            $user = $users->random();
            
            $invoice = Invoice::create([
                'customer_id' => $customer->customer_id,
                'tenant_id' => $user->tenant_id, // Add tenant_id from the user
                'invoice_number' => 'INV-DIRECT-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'invoice_date' => now()->subDays(rand(1, 60)),
                'due_date' => now()->addDays(rand(15, 30)),
                'status' => ['Draft', 'Sent', 'Partially Paid', 'Paid', 'Overdue'][rand(0, 4)],
                'subtotal' => 0,
                'tax_percentage' => 8.5,
                'tax_amount' => 0,
                'total_amount' => 0,
                'created_by_user_id' => $user->user_id,
            ]);

            // Crear items de factura
            $numItems = rand(1, 4);
            $subtotal = 0;
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                $quantity = rand(1, 8);
                $unitPrice = $product->price;
                $itemTotal = $quantity * $unitPrice;
                $subtotal += $itemTotal;

                InvoiceItem::create([
                    'invoice_id' => $invoice->invoice_id,
                    'product_id' => $product->product_id,
                    'item_name' => $product->name,
                    'item_description' => $product->description ?? 'Product description',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'item_total' => $itemTotal,
                ]);
            }

            // Actualizar totales
            $taxAmount = $subtotal * ($invoice->tax_percentage / 100);
            $totalAmount = $subtotal + $taxAmount;

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);
        }
    }

    private function createPayments(): void
    {
        $invoices = Invoice::whereIn('status', ['Sent', 'Partially Paid'])->get();
        $purchaseOrders = PurchaseOrder::whereIn('status', ['Confirmed', 'Dispatched', 'Fully Received'])->get();

        // Crear pagos para facturas
        foreach ($invoices as $invoice) {
            $paymentAmount = $invoice->total_amount - $invoice->amount_paid;
            if ($invoice->status === 'Partially Paid') {
                $paymentAmount = $paymentAmount * rand(30, 80) / 100; // Pago parcial
            }

            if ($paymentAmount > 0) {
                Payment::create([
                    'payable_type' => Invoice::class,
                    'payable_id' => $invoice->invoice_id,
                    'amount' => $paymentAmount,
                    'payment_date' => $invoice->invoice_date->addDays(rand(1, 30)),
                    'payment_method' => ['credit_card', 'bank_transfer', 'cash', 'check'][rand(0, 3)],
                    'reference_number' => 'PAY-' . str_pad($invoice->invoice_id, 4, '0', STR_PAD_LEFT),
                    'notes' => 'Payment for invoice ' . $invoice->invoice_number,
                    'tenant_id' => $invoice->tenant_id,
                    'created_by_user_id' => $invoice->created_by_user_id, // Add created_by_user_id from the invoice
                ]);
            }
        }

        // Crear pagos para órdenes de compra
        foreach ($purchaseOrders as $po) {
            $paymentAmount = $po->total_amount - $po->amount_paid;
            if ($paymentAmount > 0) {
Payment::create([
                    'payable_type' => PurchaseOrder::class,
                    'payable_id' => $po->purchase_order_id,
                    'amount' => $paymentAmount,
                    'payment_date' => $po->order_date->addDays(rand(30, 60)),
                    'payment_method' => ['bank_transfer', 'check', 'credit_card'][rand(0, 2)],
                    'reference_number' => 'PAY-PO-' . str_pad($po->purchase_order_id, 4, '0', STR_PAD_LEFT),
                    'notes' => 'Payment for purchase order ' . $po->purchase_order_number,
                    'tenant_id' => $po->tenant_id,
                    'created_by_user_id' => $po->created_by_user_id, // Add created_by_user_id from the purchase order
                ]);
            }
        }
    }

    private function assignProductStock(): void
    {
        $products = Product::all();
        $warehouses = Warehouse::all();

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                $quantity = rand(0, 200);
                $product->warehouses()->syncWithoutDetaching([
                    $warehouse->warehouse_id => ['quantity' => $quantity]
                ]);
            }
        }
    }
} 