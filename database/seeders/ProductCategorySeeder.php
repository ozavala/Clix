<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // You can seed the product_categories table with some initial data here.
        // For example, you might want to create a few categories.

        \App\Models\ProductCategory::create([
            'name' => 'Electronics',
            'tenant_id' => 1,
            'description' => 'Devices and gadgets',
        ]);

        \App\Models\ProductCategory::create([
            'name' => 'Furniture',
            'tenant_id' => 1,
            'description' => 'Home and office furniture',
        ]);

        \App\Models\ProductCategory::create([
            'name' => 'Clothing',
            'tenant_id' => 1,
            'description' => 'Apparel and accessories',
        ]);
        
        // Add more categories as needed
        \App\Models\ProductCategory::create([
            'name' => 'TShirts',
            'tenant_id' => 1,
            'description' => 'Printed and digital books',
            'parent_category_id' => 3, // Assuming this is a top-level category
        ]);
    }
}
