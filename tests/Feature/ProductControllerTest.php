<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductFeature;
use App\Models\CrmUser;
use App\Models\Permission;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ProductControllerTest extends TestCase
{
    protected CrmUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SettingsTableSeeder::class);
        $this->user = CrmUser::factory()->create();
        $this->actingAs($this->user);
        // Asignar permisos necesarios para gestión de productos
        // Esto es requerido por la lógica de autorización en el controlador de productos
        $this->givePermission($this->user, [
            'view-products',
            'create-products',
            'edit-products',
            'delete-products'
        ]);
    }

    #[Test]
    public function it_can_display_products_index()
    {
        Product::factory()->count(3)->create();

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertViewIs('products.index');
        $response->assertViewHas('products');
    }

    #[Test]
    public function it_can_search_products()
    {
        Product::factory()->create(['name' => 'Test Product']);
        Product::factory()->create(['name' => 'Another Product']);

        $response = $this->get(route('products.index', ['search' => 'Test']));

        $response->assertOk();
        $response->assertViewHas('products');
        $response->assertSee('Test Product');
        $response->assertDontSee('Another Product');
    }

    #[Test]
    public function it_can_display_create_product_form()
    {
        $response = $this->get(route('products.create'));

        $response->assertOk();
        $response->assertViewIs('products.create');
        $response->assertViewHas('categories');
    }

    #[Test]
    public function it_can_store_a_new_product()
    {
        $category = ProductCategory::factory()->create();
        $colorFeature = ProductFeature::create(['name' => 'Color']);
        $sizeFeature = ProductFeature::create(['name' => 'Size']);
        
        $productData = [
            'name' => 'Test Product',
            'description' => 'A test product description',
            'sku' => 'TEST-001',
            'price' => 99.99,
            'cost' => 50.00,
            'is_service' => false,
            'is_active' => true,
            'quantity_on_hand' => 100,
            'category_id' => $category->category_id,
            'features' => [
                ['feature_id' => $colorFeature->feature_id, 'value' => 'Red'],
                ['feature_id' => $sizeFeature->feature_id, 'value' => 'Large']
            ]
        ];

        $response = $this->post(route('products.store'), $productData);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success', 'Product/Service created successfully.');

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 99.99,
            'cost' => 50.00,
            'category_id' => $category->category_id,
        ]);

        $product = Product::where('sku', 'TEST-001')->first();
        $this->assertNotNull($product);
        $this->assertCount(2, $product->features);
    }

    #[Test]
    public function it_can_display_product_details()
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertViewIs('products.show');
        $response->assertViewHas('product');
        $response->assertSee($product->name);
    }

    #[Test]
    public function it_can_display_edit_product_form()
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.edit', $product));

        $response->assertOk();
        $response->assertViewIs('products.edit');
        $response->assertViewHas('product');
        $response->assertViewHas('categories');
    }

    #[Test]
    public function it_can_update_product()
    {
        $product = Product::factory()->create();
        $category = ProductCategory::factory()->create();
        $colorFeature = ProductFeature::create(['name' => 'Color']);
        $weightFeature = ProductFeature::create(['name' => 'Weight']);
        
        $updateData = [
            'name' => 'Updated Product',
            'description' => 'Updated description',
            'sku' => 'UPDATED-001',
            'price' => 149.99,
            'cost' => 75.00,
            'is_service' => false,
            'is_active' => true,
            'quantity_on_hand' => 150,
            'category_id' => $category->category_id,
            'features' => [
                ['feature_id' => $colorFeature->feature_id, 'value' => 'Blue'],
                ['feature_id' => $weightFeature->feature_id, 'value' => '2kg']
            ]
        ];

        $response = $this->put(route('products.update', $product), $updateData);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success', 'Product/Service updated successfully.');

        $this->assertDatabaseHas('products', [
            'product_id' => $product->product_id,
            'name' => 'Updated Product',
            'sku' => 'UPDATED-001',
            'price' => 149.99,
            'cost' => 75.00,
        ]);

        $product->refresh();
        $this->assertCount(2, $product->features);
    }

    #[Test]
    public function it_can_delete_product()
    {
        $product = Product::factory()->create();

        $response = $this->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success', 'Product/Service deleted successfully.');

        $this->assertSoftDeleted('products', ['product_id' => $product->product_id]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_product()
    {
        $response = $this->post(route('products.store'), []);

        $response->assertSessionHasErrors(['name', 'price', 'is_service', 'is_active']);
    }

    #[Test]
    public function it_validates_sku_uniqueness()
    {
        Product::factory()->create(['sku' => 'EXISTING-001']);
        
        $productData = [
            'name' => 'Test Product',
            'sku' => 'EXISTING-001',
            'price' => 99.99,
        ];

        $response = $this->post(route('products.store'), $productData);

        $response->assertSessionHasErrors(['sku']);
    }

    #[Test]
    public function it_validates_price_is_numeric()
    {
        $productData = [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 'invalid-price',
        ];

        $response = $this->post(route('products.store'), $productData);

        $response->assertSessionHasErrors(['price']);
    }

    #[Test]
    public function it_can_handle_product_features()
    {
        $category = ProductCategory::factory()->create();
        $colorFeature = ProductFeature::create(['name' => 'Color']);
        $sizeFeature = ProductFeature::create(['name' => 'Size']);
        $materialFeature = ProductFeature::create(['name' => 'Material']);
        
        $productData = [
            'name' => 'Feature Test Product',
            'description' => 'A product with features',
            'sku' => 'FEATURE-001',
            'price' => 99.99,
            'cost' => 50.00,
            'is_service' => false,
            'is_active' => true,
            'quantity_on_hand' => 100,
            'category_id' => $category->category_id,
            'features' => [
                ['feature_id' => $colorFeature->feature_id, 'value' => 'Red'],
                ['feature_id' => $sizeFeature->feature_id, 'value' => 'Large'],
                ['feature_id' => $materialFeature->feature_id, 'value' => 'Cotton']
            ]
        ];

        $response = $this->post(route('products.store'), $productData);

        $response->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'FEATURE-001')->first();
        $this->assertNotNull($product);
        $this->assertCount(3, $product->features);
        
        // Check that features are properly stored
        $featureNames = $product->features->pluck('name')->toArray();
        $this->assertContains('Color', $featureNames);
        $this->assertContains('Size', $featureNames);
        $this->assertContains('Material', $featureNames);
    }
} 