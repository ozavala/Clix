<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
            'tenant_id' => 1,
            'name' => 'Laptop',
            'description' => 'High-performance laptop with 16GB RAM and 512GB SSD.',
            'sku' => 'LAPTOP-001',
            'price' => 1200.00,
            'quantity_on_hand' => 50,
            'is_service' => false,
            'is_active' => true, // 
            'created_by_user_id' => 1, // Assuming user with ID 1 exists
            'product_category_id' => 1, // Assuming category ID 1 exists for electronics
            ]) ->features()->attach([
            1 => ['value' => '16GB RAM'],
            2 => ['value' => '512GB SSD'],
        ]);
        Product::create([
            'tenant_id' => 1,
            'name' => 'Smartphone',
            'description' => 'Latest smartphone with 128GB storage and 6GB RAM.',
            'sku' => 'SMARTPHONE-001',
            'price' => 800.00,
            'quantity_on_hand' => 100,
            'is_service' => false,  
            'is_active' => true, 
            'created_by_user_id' => 1, // Assuming user with ID 1 exists
            'product_category_id' => 1, // Assuming category ID 2 exists for computers
            ]) ->features()->attach([
            3 => ['value' => '128GB Storage'],
            4 => ['value' => '6GB RAM'],
        ]);
        Product::create([
            'tenant_id' => 1,
            'name' => 'Office Chair',
            'description' => 'Ergonomic office chair with adjustable height and lumbar support.',
            'sku' => 'CHAIR-001',
            'price' => 150.00,
            'quantity_on_hand' => 200,
            'is_service' => false,  
            'is_active' => true, 
            'created_by_user_id' => 1, // Assuming user with ID 1 exists
            'product_category_id' => 2, // Assuming category ID 3 exists for furniture
            ]) ->features()->attach([
            5 => ['value' => 'Adjustable Height'],
            6 => ['value' => 'Lumbar Support'],
        ]);
        Product::create([
            'tenant_id' => 1,
            'name' => 'Consulting Service',
            'description' => 'Professional consulting service for business strategy.',
            'sku' => 'CONSULT-001',
            'price' => 300.00,
            'quantity_on_hand' => 0, // Services typically don't have stock
            'is_service' => true,  
            'is_active' => true, 
            'created_by_user_id' => 1, // Assuming user with ID 1 exists
            'product_category_id' => 3, // Assuming category ID 4 exists for services
        ]);
        Product::create([
            'tenant_id' => 1,
            'name' => 'Web Development Service',
            'description' => 'Custom web development service for businesses.',
            'sku' => 'WEBDEV-001',
            'price' => 1500.00,
            'quantity_on_hand' => 0, // Services typically don't have stock
            'is_service' => true,  
            'is_active' => true, 
            'created_by_user_id' => 1, // Assuming user with ID 1 exists
            'product_category_id' => 3, // Assuming category ID 4 exists for services
        ]);
        Product::create([
            'tenant_id' => 1,
            'name' => 'Graphic Design Service',
            'description' => 'Professional graphic design service for branding and marketing.',
            'sku' => 'GRAPHIC-001',
            'price' => 500.00,
            'quantity_on_hand' => 0, // Services typically don't have stock
            'is_service' => true,  
            'is_active' => true, 
            'created_by_user_id' => 1, // Assuming user with ID 1 exists
            'product_category_id' => 3, // Assuming category ID 4 exists for services
        ]);
    }
}
       
