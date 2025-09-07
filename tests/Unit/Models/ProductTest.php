<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    public function test_product_belongs_to_category()
    {
        $category = ProductCategory::factory()->forTenant($this->tenant)->create();
        $product = Product::factory()->forTenant($this->tenant)->for($category, 'category')->create();

        $this->assertInstanceOf(ProductCategory::class, $product->category);
        $this->assertTrue($product->category->is($category));
    }

    public function test_product_can_be_in_multiple_warehouses()
    {
        $product = Product::factory()->forTenant($this->tenant)->create();
        $warehouses = Warehouse::factory()->count(2)->forTenant($this->tenant)->create();

        $product->warehouses()->attach($warehouses->pluck('warehouse_id'));
        $this->assertCount(2, $product->warehouses);
        $this->assertInstanceOf(Warehouse::class, $product->warehouses->first());
    }
} 