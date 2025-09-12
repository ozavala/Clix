<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\LandedCost;
use App\Models\CrmUser;
use App\Services\LandedCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class UnitPriceCalculationTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected $tenant;
    protected $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable transaction for this test
        $this->withoutExceptionHandling();
        
        // Create a tenant without specifying ID to avoid unique constraint issues
        $this->tenant = \App\Models\Tenant::factory()->create([
            'name' => 'Test Tenant ' . uniqid(),
            'legal_id' => 'TEST-' . uniqid(),
            'is_active' => true,
        ]);
        
        // Create a user for the tenant
        $this->user = CrmUser::factory()->forTenant($this->tenant)->create([
            'email' => 'test-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $this->actingAs($this->user, 'crm');
    }
    
    protected function tearDown(): void
    {
        // Clean up tenant and user
        if ($this->tenant) {
            $this->tenant->delete();
        }
        
        parent::tearDown();
    }

    public function test_unit_price_calculation_with_landed_costs()
    {
        // Create supplier with tenant context
        $supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create product with tenant context
        $product = Product::factory()->create([
            'cost' => 200.00,
            'quantity_on_hand' => 0,
            'created_by_user_id' => $this->user->user_id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create purchase order with tenant context
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->supplier_id,
            'created_by_user_id' => $this->user->user_id,
            'purchase_order_number' => 'PO-TEST-' . uniqid(),
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(30),
            'type' => 'Import',
            'status' => 'confirmed',
            'subtotal' => 0,
            'tax_percentage' => 10,
            'tax_amount' => 0,
            'shipping_cost' => 100.00,
            'other_charges' => 0,
            'total_amount' => 0,
            'amount_paid' => 0,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create single item
        $quantity = 50;
        $baseUnitPrice = 200.00;
        $itemTotal = $quantity * $baseUnitPrice;

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => $product->product_id,
            'item_name' => $product->name,
            'item_description' => $product->description ?? 'Test Item',
            'quantity' => $quantity,
            'unit_price' => $baseUnitPrice,
            'item_total' => $itemTotal,
            'landed_cost_per_unit' => null,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create landed costs
        $landedCosts = [
            ['description' => 'Freight charges', 'amount' => 500.00],
            ['description' => 'Customs duties', 'amount' => 300.00],
            ['description' => 'Insurance', 'amount' => 100.00],
        ];

        foreach ($landedCosts as $cost) {
            LandedCost::create([
                'costable_type' => PurchaseOrder::class,
                'costable_id' => $purchaseOrder->purchase_order_id,
                'description' => $cost['description'],
                'amount' => $cost['amount'],
                'tenant_id' => $this->tenant->id,
            ]);
        }

        // Recargar la relación para asegurar que el item esté en memoria
        $purchaseOrder->load('items');
        // Actualizar el subtotal del PO para que coincida con el item_total
        $purchaseOrder->subtotal = $itemTotal;
        $purchaseOrder->save();
        $purchaseOrder->refresh();
        $purchaseOrder->load('items');
        // Calculate and apportion landed costs
        $landedCostService = new LandedCostService();
        $landedCostService->apportionCosts($purchaseOrder);

        // Update purchase order totals
        $totalLandedCosts = $purchaseOrder->landedCosts()->sum('amount');
        $subtotal = $itemTotal;
        $taxAmount = ($subtotal * $purchaseOrder->tax_percentage) / 100;
        $totalAmount = $subtotal + $taxAmount + $purchaseOrder->shipping_cost + $totalLandedCosts;

        $purchaseOrder->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);

        // Refresh the item to get updated landed_cost_per_unit
        $item->refresh();

        // Debug: Check what values we have
        $poSubtotal = $purchaseOrder->items()->sum('item_total');
        $valueProportion = $item->item_total / $poSubtotal;
        $expectedLandedCostForItem = $totalLandedCosts * $valueProportion;
        $expectedLandedCostPerUnit = $expectedLandedCostForItem / $quantity;
        
        \Log::info("Debug values:", [
            'total_landed_costs' => $totalLandedCosts,
            'quantity' => $quantity,
            'expected_landed_cost_per_unit' => $expectedLandedCostPerUnit,
            'actual_landed_cost_per_unit' => $item->landed_cost_per_unit,
            'item_total' => $item->item_total,
            'po_subtotal' => $poSubtotal,
            'value_proportion' => $valueProportion,
            'expected_landed_cost_for_item' => $expectedLandedCostForItem,
        ]);
        
        $this->assertEquals(
            round($expectedLandedCostPerUnit, 2),
            round($item->landed_cost_per_unit, 2),
            'Landed cost per unit should be calculated correctly',
            0.01 // Tolerancia de 0.01 para diferencias de redondeo
        );

        // Calculate final unit price
        $finalUnitPrice = $baseUnitPrice + $item->landed_cost_per_unit;
        $expectedFinalUnitPrice = $baseUnitPrice + $expectedLandedCostPerUnit;

        \Log::info("Final unit price debug:", [
            'base_unit_price' => $baseUnitPrice,
            'landed_cost_per_unit' => $item->landed_cost_per_unit,
            'expected_landed_cost_per_unit' => $expectedLandedCostPerUnit,
            'final_unit_price' => $finalUnitPrice,
            'expected_final_unit_price' => $expectedFinalUnitPrice,
        ]);

        $this->assertEquals(
            round($expectedFinalUnitPrice, 2),
            round($finalUnitPrice, 2),
            'Final unit price should include base cost plus landed costs',
            0.01 // Tolerancia de 0.01 para diferencias de redondeo
        );

        // Update product cost with landed costs
        $product->receiveStock($quantity, $finalUnitPrice);

        // Verify product cost was updated
        $this->assertEquals(
            round($finalUnitPrice, 2),
            round($product->cost, 2),
            'Product cost should be updated with landed costs',
            0.01 // Tolerancia de 0.01 para diferencias de redondeo
        );

        // Verify product quantity was updated
        $this->assertEquals($quantity, $product->quantity_on_hand);

        // Verify purchase order totals
        $purchaseOrder->refresh();
        $expectedTotalAmount = $subtotal + $taxAmount + $purchaseOrder->shipping_cost + $totalLandedCosts;

        $this->assertEquals(
            round($expectedTotalAmount, 2),
            round($purchaseOrder->total_amount, 2),
            'Purchase order total should include all costs'
        );
    }

    public function test_multiple_products_with_different_landed_costs()
    {
        // Create supplier with tenant context
        $supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create products with tenant context
        $products = [
            Product::factory()->create([
                'name' => 'Product A',
                'cost' => 100.00,
                'quantity_on_hand' => 0, // Sin stock inicial
                'created_by_user_id' => $this->user->user_id,
                'tenant_id' => $this->tenant->id,
            ]),
            Product::factory()->create([
                'name' => 'Product B',
                'cost' => 200.00,
                'quantity_on_hand' => 0, // Sin stock inicial
                'created_by_user_id' => $this->user->user_id,
                'tenant_id' => $this->tenant->id,
            ]),
        ];

        // Create purchase order with tenant context
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->supplier_id,
            'purchase_order_number' => 'PO-TEST-' . uniqid(),
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(30),
            'type' => 'Import',
            'status' => 'Confirmed',
            'subtotal' => 0,
            'tax_percentage' => 10,
            'tax_amount' => 0,
            'shipping_cost' => 100.00,
            'other_charges' => 0,
            'total_amount' => 0,
            'amount_paid' => 0,
            'created_by_user_id' => $this->user->user_id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create items for each product
        $items = [];
        foreach ($products as $index => $product) {
            $quantity = 10 + ($index * 5); // 10 for first product, 15 for second
            $unitPrice = $product->cost;
            $itemTotal = $quantity * $unitPrice;

            $item = PurchaseOrderItem::factory()->create([
                'purchase_order_id' => $purchaseOrder->purchase_order_id,
                'product_id' => $product->product_id,
                'item_name' => $product->name,
                'item_description' => $product->description ?? 'Test Item',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'item_total' => $itemTotal,
                'landed_cost_per_unit' => null,
                'tenant_id' => $this->tenant->id,
            ]);

            $items[] = $item;
        }

        // Create landed costs
        $landedCosts = [
            ['description' => 'Freight charges', 'amount' => 500.00],
            ['description' => 'Customs duties', 'amount' => 300.00],
            ['description' => 'Insurance', 'amount' => 100.00],
        ];

        foreach ($landedCosts as $cost) {
            LandedCost::create([
                'costable_type' => PurchaseOrder::class,
                'costable_id' => $purchaseOrder->purchase_order_id,
                'description' => $cost['description'],
                'amount' => $cost['amount'],
                'tenant_id' => $this->tenant->id,
            ]);
        }

        // Recargar la relación para asegurar que el item esté en memoria
        $purchaseOrder->load('items');
        // Actualizar el subtotal del PO para que coincida con el item_total
        $purchaseOrder->subtotal = $itemTotal;
        $purchaseOrder->save();
        $purchaseOrder->refresh();
        $purchaseOrder->load('items');
        // Calculate and apportion landed costs
        $landedCostService = new LandedCostService();
        $landedCostService->apportionCosts($purchaseOrder);

        // Update purchase order totals
        $totalLandedCosts = $purchaseOrder->landedCosts()->sum('amount');
        $subtotal = $itemTotal;
        $taxAmount = ($subtotal * $purchaseOrder->tax_percentage) / 100;
        $totalAmount = $subtotal + $taxAmount + $purchaseOrder->shipping_cost + $totalLandedCosts;

        $purchaseOrder->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);

        // Verify landed costs are apportioned proportionally
        $totalItemValue = collect($items)->sum('item_total');

        foreach ($items as $item) {
            $item->refresh();
            
            // Calculate expected landed cost per unit based on proportional value
            $itemProportion = $item->item_total / $totalItemValue;
            $expectedLandedCostForItem = $totalLandedCosts * $itemProportion;
            $expectedLandedCostPerUnit = $expectedLandedCostForItem / $item->quantity;

            $this->assertEquals(
                round($expectedLandedCostPerUnit, 2),
                round($item->landed_cost_per_unit, 2),
                "Landed cost per unit for {$item->item_name} should be calculated proportionally"
            );

            // Update product cost
            $finalUnitPrice = $item->unit_price + $item->landed_cost_per_unit;
            $product = collect($products)->firstWhere('product_id', $item->product_id);
            $product->receiveStock($item->quantity, $finalUnitPrice);

            $this->assertEquals(
                round($finalUnitPrice, 2),
                round($product->cost, 2),
                "Product cost for {$product->name} should be updated with landed costs"
            );
        }
    }

    public function test_landed_cost_calculation_with_zero_quantities()
    {
        // Create supplier with tenant context
        $supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create product with tenant context
        $product = Product::factory()->create([
            'cost' => 100.00,
            'quantity_on_hand' => 0,
            'created_by_user_id' => $this->user->user_id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create purchase order with tenant context
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->supplier_id,
            'created_by_user_id' => $this->user->user_id,
            'purchase_order_number' => 'PO-ZERO-' . uniqid(),
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(30),
            'type' => 'Import',
            'status' => 'confirmed',
            'subtotal' => 0,
            'tax_percentage' => 10,
            'tax_amount' => 0,
            'shipping_cost' => 100.00,
            'other_charges' => 0,
            'total_amount' => 0,
            'amount_paid' => 0,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create purchase order item with zero quantity
        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => $product->product_id,
            'item_name' => $product->name,
            'item_description' => $product->description ?? 'Test Item',
            'quantity' => 0,
            'unit_price' => 100.00,
            'item_total' => 0,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create landed cost for the purchase order
        LandedCost::create([
            'costable_type' => PurchaseOrder::class,
            'costable_id' => $purchaseOrder->purchase_order_id,
            'cost_type' => 'freight',
            'amount' => 100.00,
            'description' => 'Freight charges',
            'tenant_id' => $this->tenant->id,
        ]);

        // Process landed costs
        $landedCostService = new LandedCostService();
        $landedCostService->apportionCosts($purchaseOrder);

        // Refresh the item
        $item = $item->fresh();

        // Should handle zero quantity gracefully
        $this->assertNotNull($item->landed_cost_per_unit);
        $this->assertEquals(0, $item->landed_cost_per_unit, 'Landed cost per unit should be 0 for zero quantity');
    }
} 