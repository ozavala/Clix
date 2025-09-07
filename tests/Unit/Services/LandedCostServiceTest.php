<?php

namespace Tests\Unit\Services;

use App\Models\CrmUser;
use App\Models\LandedCost;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Services\LandedCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandedCostServiceTest extends TestCase
{
    use RefreshDatabase;

    private LandedCostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LandedCostService();
    }

    public function test_apportions_costs_correctly_across_items()
    {
        // Create a purchase order with items
        $purchaseOrder = PurchaseOrder::factory()->forTenant($this->tenant)->create([
            'supplier_id' => Supplier::factory()->forTenant($this->tenant)->create()->supplier_id,
            'purchase_order_number' => 'PO-002',
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(7),
            'type' => 'Standard',
            'status' => 'Draft',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'created_by_user_id' => CrmUser::factory()->forTenant($this->tenant)->create()->user_id,
        ]);

        $product1 = Product::factory()->forTenant($this->tenant)->create([
            'name' => 'Test Product 1',
            'description' => 'Test Description 1',
            'sku' => 'TEST-002',
            'price' => 300.00,
            'cost' => 150.00,
            'quantity_on_hand' => 10,
            'is_service' => false,
            'is_active' => true,
            'created_by_user_id' => CrmUser::factory()->forTenant($this->tenant)->create()->user_id,
        ]);

        $product2 = Product::factory()->forTenant($this->tenant)->create([
            'name' => 'Test Product 2',
            'description' => 'Test Description 2',
            'sku' => 'TEST-003',
            'price' => 400.00,
            'cost' => 200.00,
            'quantity_on_hand' => 10,
            'is_service' => false,
            'is_active' => true,
            'created_by_user_id' => CrmUser::factory()->forTenant($this->tenant)->create()->user_id,
        ]);

        // Create items with specific values
        $item1 = PurchaseOrderItem::factory()->forTenant($this->tenant)->create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => $product1->product_id,
            'item_name' => 'Test Item 1',
            'item_description' => 'Test Description 1',
            'quantity' => 2,
            'unit_price' => 300.00,
            'item_total' => 600.00,
        ]);

        $item2 = PurchaseOrderItem::factory()->forTenant($this->tenant)->create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => $product2->product_id,
            'item_name' => 'Test Item 2',
            'item_description' => 'Test Description 2',
            'quantity' => 1,
            'unit_price' => 400.00,
            'item_total' => 400.00,
        ]);

        // Create landed costs
        LandedCost::factory()->forTenant($this->tenant)->create([
            'costable_type' => 'App\\Models\\PurchaseOrder',
            'costable_id' => $purchaseOrder->purchase_order_id,
            'description' => 'Test landed cost 1',
            'amount' => 100.00,
        ]);

        LandedCost::factory()->forTenant($this->tenant)->create([
            'costable_type' => 'App\\Models\\PurchaseOrder',
            'costable_id' => $purchaseOrder->purchase_order_id,
            'description' => 'Test landed cost 2',
            'amount' => 50.00,
        ]);

        // Act
        $this->service->apportionCosts($purchaseOrder);

        // Debug: Check the raw database value first
        $rawValue1 = \DB::table('purchase_order_items')
            ->where('purchase_order_item_id', $item1->purchase_order_item_id)
            ->value('landed_cost_per_unit');
        $rawValue2 = \DB::table('purchase_order_items')
            ->where('purchase_order_item_id', $item2->purchase_order_item_id)
            ->value('landed_cost_per_unit');
        
        \Log::info("Raw DB values - Item1: {$rawValue1}, Item2: {$rawValue2}");

        // Assert
        $item1->refresh();
        $item2->refresh();

        \Log::info("Model values - Item1: {$item1->landed_cost_per_unit}, Item2: {$item2->landed_cost_per_unit}");

        // Debug: Check if the field exists and has a value
        $this->assertNotNull($item1->landed_cost_per_unit, 'landed_cost_per_unit should not be null');
        $this->assertNotNull($item2->landed_cost_per_unit, 'landed_cost_per_unit should not be null');

        // Item1 should get 60% of landed costs (600/1000 * 150 = 90)
        // 90 / 2 units = 45 per unit
        $this->assertEquals('45.0000', $item1->landed_cost_per_unit);

        // Item2 should get 40% of landed costs (400/1000 * 150 = 60)
        // 60 / 1 unit = 60 per unit
        $this->assertEquals('60.0000', $item2->landed_cost_per_unit);
    }

    public function test_simple_landed_cost_calculation()
    {
        \Log::info("Starting simple landed cost calculation test");
        
        // Create a simple test with one item and one landed cost
        $purchaseOrder = PurchaseOrder::factory()->forTenant($this->tenant)->create([
            'supplier_id' => Supplier::factory()->forTenant($this->tenant)->create()->supplier_id,
            'purchase_order_number' => 'PO-001',
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(7),
            'type' => 'Standard',
            'status' => 'Draft',
            'subtotal' => 100.00,
            'total_amount' => 100.00,
            'created_by_user_id' => CrmUser::factory()->forTenant($this->tenant)->create()->user_id,
        ]);

        $product = Product::factory()->forTenant($this->tenant)->create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'sku' => 'TEST-001',
            'price' => 100.00,
            'cost' => 50.00,
            'quantity_on_hand' => 10,
            'is_service' => false,
            'is_active' => true,
            'created_by_user_id' => CrmUser::factory()->forTenant($this->tenant)->create()->user_id,
        ]);

        $item = PurchaseOrderItem::factory()->forTenant($this->tenant)->create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => $product->product_id,
            'item_name' => 'Test Item',
            'item_description' => 'Test Description',
            'quantity' => 1,
            'unit_price' => 100.00,
            'item_total' => 100.00,
        ]);

        LandedCost::factory()->forTenant($this->tenant)->create([
            'costable_type' => 'App\\Models\\PurchaseOrder',
            'costable_id' => $purchaseOrder->purchase_order_id,
            'description' => 'Test landed cost',
            'amount' => 10.00,
        ]);

        // Act
        $this->service->apportionCosts($purchaseOrder);

        // Check raw database value
        $rawValue = \DB::table('purchase_order_items')
            ->where('purchase_order_item_id', $item->purchase_order_item_id)
            ->value('landed_cost_per_unit');
        
        \Log::info("Simple test - Raw DB value: {$rawValue}");

        // Assert
        $item->refresh();
        \Log::info("Simple test - Model value: {$item->landed_cost_per_unit}");

        $this->assertNotNull($item->landed_cost_per_unit, 'landed_cost_per_unit should not be null');
        $this->assertEquals('10.0000', $item->landed_cost_per_unit);
        
        \Log::info("Simple test completed successfully");
    }

    public function test_handles_zero_subtotal_gracefully()
    {
        // Create a purchase order with zero subtotal
        $purchaseOrder = PurchaseOrder::factory()->forTenant($this->tenant)->create([
            'subtotal' => 0.00,
            'total_amount' => 0.00,
        ]);

        // Create an item with zero value
        PurchaseOrderItem::factory()->forTenant($this->tenant)->create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => Product::factory()->forTenant($this->tenant)->create()->product_id,
            'item_name' => 'Test Item',
            'item_description' => 'Test Description',
            'quantity' => 1,
            'unit_price' => 0.00,
            'item_total' => 0.00,
        ]);

        // Create landed costs
        LandedCost::factory()->forTenant($this->tenant)->create([
            'costable_type' => 'App\\Models\\PurchaseOrder',
            'costable_id' => $purchaseOrder->purchase_order_id,
            'amount' => 100.00,
        ]);

        // Act - should not throw exception
        $this->service->apportionCosts($purchaseOrder);

        // Assert - method should complete without error
        $this->assertTrue(true);
    }

    public function test_handles_zero_quantity_items()
    {
        \Log::info("Starting zero quantity test");
        
        // Create a purchase order
        $purchaseOrder = PurchaseOrder::factory()->forTenant($this->tenant)->create([
            'supplier_id' => Supplier::factory()->forTenant($this->tenant)->create()->supplier_id,
            'purchase_order_number' => 'PO-003',
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(7),
            'type' => 'Standard',
            'status' => 'Draft',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'created_by_user_id' => CrmUser::factory()->forTenant($this->tenant)->create()->user_id,
        ]);

        \Log::info("Created PO with ID: " . $purchaseOrder->purchase_order_id);

        $product = Product::factory()->forTenant($this->tenant)->create([
            'name' => 'Test Product Zero',
            'description' => 'Test Description Zero',
            'sku' => 'TEST-004',
            'price' => 100.00,
            'cost' => 50.00,
            'quantity_on_hand' => 10,
            'is_service' => false,
            'is_active' => true,
            'created_by_user_id' => CrmUser::factory()->forTenant($this->tenant)->create()->user_id,
        ]);

        \Log::info("Created product with ID: " . $product->product_id);

        // Create an item with zero quantity
        $item = PurchaseOrderItem::factory()->forTenant($this->tenant)->create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => $product->product_id,
            'item_name' => 'Test Item',
            'item_description' => 'Test Description',
            'quantity' => 0,
            'unit_price' => 100.00,
            'item_total' => 0.00,
        ]);

        \Log::info("Created item with ID: " . $item->purchase_order_item_id);

        // Create landed costs
        LandedCost::factory()->forTenant($this->tenant)->create([
            'costable_type' => 'App\\Models\\PurchaseOrder',
            'costable_id' => $purchaseOrder->purchase_order_id,
            'description' => 'Test landed cost zero',
            'amount' => 50.00,
        ]);

        \Log::info("Created landed cost");

        // Act
        $this->service->apportionCosts($purchaseOrder);

        \Log::info("Service executed");

        // Check raw database value
        $rawValue = \DB::table('purchase_order_items')
            ->where('purchase_order_item_id', $item->purchase_order_item_id)
            ->value('landed_cost_per_unit');
        
        \Log::info("Zero quantity test - Raw DB value: {$rawValue}");

        // Assert
        $item->refresh();
        \Log::info("Zero quantity test - Model value: {$item->landed_cost_per_unit}");

        $this->assertEquals('0.0000', $item->landed_cost_per_unit);
    }

    public function test_handles_multiple_landed_costs()
    {
        // Create a purchase order
        $purchaseOrder = PurchaseOrder::factory()->forTenant($this->tenant)->create([
            'supplier_id' => Supplier::factory()->forTenant($this->tenant)->create()->supplier_id,
            'purchase_order_number' => 'PO-004',
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(7),
            'type' => 'Standard',
            'status' => 'Draft',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'created_by_user_id' => CrmUser::factory()->forTenant($this->tenant)->create()->user_id,
        ]);

        $product = Product::factory()->forTenant($this->tenant)->create([
            'name' => 'Test Product Multiple',
            'description' => 'Test Description Multiple',
            'sku' => 'TEST-005',
            'price' => 100.00,
            'cost' => 50.00,
            'quantity_on_hand' => 10,
            'is_service' => false,
            'is_active' => true,
            'created_by_user_id' => CrmUser::factory()->forTenant($this->tenant)->create()->user_id,
        ]);

        // Create a single item
        $item = PurchaseOrderItem::factory()->forTenant($this->tenant)->create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => $product->product_id,
            'item_name' => 'Test Item',
            'item_description' => 'Test Description',
            'quantity' => 10,
            'unit_price' => 100.00,
            'item_total' => 1000.00,
        ]);

        // Create multiple landed costs
        LandedCost::factory()->forTenant($this->tenant)->create([
            'costable_type' => 'App\\Models\\PurchaseOrder',
            'costable_id' => $purchaseOrder->purchase_order_id,
            'description' => 'Test landed cost 1',
            'amount' => 100.00,
        ]);

        LandedCost::factory()->forTenant($this->tenant)->create([
            'costable_type' => 'App\\Models\\PurchaseOrder',
            'costable_id' => $purchaseOrder->purchase_order_id,
            'description' => 'Test landed cost 2',
            'amount' => 50.00,
        ]);

        LandedCost::factory()->forTenant($this->tenant)->create([
            'costable_type' => 'App\\Models\\PurchaseOrder',
            'costable_id' => $purchaseOrder->purchase_order_id,
            'description' => 'Test landed cost 3',
            'amount' => 25.00,
        ]);

        // Act
        $this->service->apportionCosts($purchaseOrder);

        // Assert
        $item->refresh();
        // Total landed costs: 100 + 50 + 25 = 175
        // 175 / 10 units = 17.5 per unit
        $this->assertEquals('17.5000', $item->landed_cost_per_unit);
    }
}