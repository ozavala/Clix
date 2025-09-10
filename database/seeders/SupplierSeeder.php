<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier; // Adjust the namespace according to your application structure

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::create([
            'tenant_id' => 1,
            'name' => 'ABC Supplies',
            'legal_id' => 'SUP-0001',
            'contact_person' => 'John Smith',
            'email' => 'john@example.com',
            'phone_number' => '123-456-7890',
            'noteable_id' => 1,
            'noteable_type' => 'App\Models\Supplier',
        
            ])->addresses()->create([
            'addressable_id' => 1,
            'addressable_type' => 'App\Models\Supplier',
            'address_type' => 'Primary',
            'street_address_line_1' => '123 Main St',
            'city' => 'Springfield',
            'state_province' => 'IL',
            'postal_code' => '62701',
            'country_code' => 'US',
            'is_primary' => true,
        ]);
        Supplier::create([
            'tenant_id' => 1,
            'name' => 'XYZ Distributors',
            'legal_id' => 'SUP-0002',
            'contact_person' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone_number' => '987-654-3210',
            'noteable_id' => 2,
            'noteable_type' => 'App\Models\Supplier',
        ])->addresses()->create([
            'addressable_id' => 2,
            'addressable_type' => 'App\Models\Supplier',
            'address_type' => 'Billing',
            'street_address_line_1' => '456 Elm St',
            'city' => 'Shelbyville',
            'state_province' => 'IL',
            'postal_code' => '62565',
            'country_code' => 'US',
            'is_primary' => true,
        ]);
        Supplier::create([
            'tenant_id' => 1,
            'name' => 'Global Traders',
            'legal_id' => 'SUP-0003',
            'contact_person' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'phone_number' => '555-123-4567',
            'noteable_id' => 3,
            'noteable_type' => 'App\Models\Supplier',
        ])->addresses()->create([
            'addressable_id' => 3,
            'addressable_type' => 'App\Models\Supplier',
            'address_type' => 'Shipping',
            'street_address_line_1' => '789 Oak St',
            'city' => 'Capital City',
            'state_province' => 'IL',
            'postal_code' => '62702',
            'country_code' => 'US',
            'is_primary' => true,
        ]);
        Supplier::create([
            'tenant_id' => 1,
            'name' => 'Local Goods',
            'legal_id' => 'SUP-0004',
            'contact_person' => 'Bob Brown',
            'email' => 'bob@example.com',
            'phone_number' => '321-654-0987',
            'noteable_id' => 4,
            'noteable_type' => 'App\Models\Supplier',
        ])->addresses()->create([
            'addressable_id' => 4,
            'addressable_type' => 'App\Models\Supplier',
            'address_type' => 'Primary',
            'street_address_line_1' => '321 Pine St',
            'city' => 'Greenfield',
            'state_province' => 'IL',
            'postal_code' => '62703',
            'country_code' => 'US',
            'is_primary' => true,
        ]);     
    }
}
