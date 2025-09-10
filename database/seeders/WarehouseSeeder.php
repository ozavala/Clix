<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Warehouse; // Assuming you have a Warehouse model

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouse1 = Warehouse::create([
            'tenant_id' => 1,
            'name' => 'Main Warehouse',
            'location' => 'Main Warehouse',
             'addressable_id' => 1,
            'addressable_type' => 'App\Models\Warehouse',    
            'is_active' => true,
        ]);
        $warehouse1->addresses()->create([
            'address_type' => 'Primary',
            'addressable_id' => 1,
            'addressable_type' => 'App\Models\Warehouse',
            'street_address_line_1' => '123 Main St',
            'city' => 'Anytown',
            'state_province' => 'CA',
            'postal_code' => '90210',
            'country_code' => 'US',
            'is_primary' => true,
        ]);

        $warehouse2 = Warehouse::create([
            'tenant_id' => 1,
            'name' => 'Secondary Warehouse',
            'location' => 'Secondary Warehouse',
            'addressable_id' => 2,
            'addressable_type' => 'App\Models\Warehouse',    
            'is_active' => true,
        ]);
        $warehouse2->addresses()->create([
            'address_type' => 'Primary',
            'addressable_id' => 2,
            'addressable_type' => 'App\Models\Warehouse',
            'street_address_line_1' => '456 Oak Ave',
            'city' => 'Otherville',
            'state_province' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
            'is_primary' => true,
        ]);
    }
}
