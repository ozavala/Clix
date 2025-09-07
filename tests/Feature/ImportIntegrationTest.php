<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\LandedCost;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\CrmUser;
use App\Services\LandedCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Carbon\Carbon;

class ImportIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected CrmUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = CrmUser::factory()->forTenant($this->tenant)->create();
        $this->actingAs($this->user);
    }

    #[Test]
    public function complete_import_flow_with_landed_costs()
    {
        // Step 1: Create a supplier for import
        $supplier = Supplier::factory()->forTenant($this->tenant)->create([
            'name' => 'Import Supplier Co.',
            'email' => 'import@supplier.com',
            'phone_number' => '+1-555-IMPORT',
        ]);

        // Step 2: Create products that will be imported
        $product1 = Product::factory()->forTenant($this->tenant)->create([
            'name' => 'Imported Electronics',
            'sku' => 'IMP-ELEC-001',
            'price' => 500.00,
            'cost' => 200.00, // Initial cost before landed costs
            'quantity_on_hand' => 0,
        ]);

        $product2 = Product::factory()->forTenant($this->tenant)->create([
            'name' => 'Imported Components',
            'sku' => 'IMP-COMP-001',
            'price' => 300.00,
            'cost' => 150.00, // Initial cost before landed costs
            'quantity_on_hand' => 0,
        ]);

        // Step 3: Create a purchase order for import
        $purchaseOrder = PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $supplier->supplier_id,
            'purchase_order_number' => 'PO-IMPORT-001',
            'order_date' => Carbon::now(),
            'expected_delivery_date' => Carbon::now()->addDays(30),
            'type' => 'Import',
            'status' => 'Confirmed',
            'subtotal' => 5000.00,
            'tax_amount' => 500.00,
            'total_amount' => 5500.00,
            'amount_paid' => 0.00,
            'created_by_user_id' => $this->user->user_id,
        ]);

        // Step 4: Add items to the purchase order
        $item1 = PurchaseOrderItem::create([
            'tenant_id' => $this->tenant->id,
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => $product1->product_id,
            'item_name' => $product1->name,
            'item_description' => 'High-quality imported electronics',
            'quantity' => 10,
            'unit_price' => 300.00,
            'item_total' => 3000.00,
        ]);

        $item2 = PurchaseOrderItem::create([
            'tenant_id' => $this->tenant->id,
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => $product2->product_id,
            'item_name' => $product2->name,
            'item_description' => 'Electronic components for assembly',
            'quantity' => 20,
            'unit_price' => 100.00,
            'item_total' => 2000.00,
        ]);

        // Step 5: Add landed costs for the import
        $landedCosts = [
            [
                'description' => 'Freight charges',
                'amount' => 800.00,
            ],
            [
                'description' => 'Customs duties',
                'amount' => 400.00,
            ],
            [
                'description' => 'Insurance',
                'amount' => 200.00,
            ],
            [
                'description' => 'Bank transfer fees',
                'amount' => 50.00,
            ],
            [
                'description' => 'Handling charges',
                'amount' => 150.00,
            ],
            [
                'description' => 'Customs broker fees',
                'amount' => 300.00,
            ],
        ];

        foreach ($landedCosts as $cost) {
            LandedCost::create([
                'tenant_id' => $this->tenant->id,
                'costable_type' => PurchaseOrder::class,
                'costable_id' => $purchaseOrder->purchase_order_id,
                'description' => $cost['description'],
                'amount' => $cost['amount'],
            ]);
        }

        // Step 6: Calculate and apportion landed costs
        $landedCostService = new LandedCostService();
        $landedCostService->apportionCosts($purchaseOrder);

        // Step 7: Verify landed costs are apportioned correctly
        $item1->refresh();
        $item2->refresh();

        // Item1: 3000/5000 = 60% of landed costs
        // Total landed costs: 1900
        // Item1 gets: 1900 * 0.6 = 1140 / 10 units = 114 per unit
        $this->assertEquals('114.0000', $item1->landed_cost_per_unit);

        // Item2: 2000/5000 = 40% of landed costs
        // Item2 gets: 1900 * 0.4 = 760 / 20 units = 38 per unit
        $this->assertEquals('38.0000', $item2->landed_cost_per_unit);

        // Step 8: Simulate goods receipt and update product costs
        $product1->receiveStock(10, $item1->unit_price + $item1->landed_cost_per_unit);
        $product2->receiveStock(20, $item2->unit_price + $item2->landed_cost_per_unit);

        // Step 9: Verify product costs are updated with landed costs
        $product1->refresh();
        $product2->refresh();

        // Product1: (200 * 0 + 414 * 10) / 10 = 414
        $this->assertEquals(414.00, $product1->cost);
        $this->assertEquals(10, $product1->quantity_on_hand);

        // Product2: (150 * 0 + 138 * 20) / 20 = 138
        $this->assertEquals(138.00, $product2->cost);
        $this->assertEquals(20, $product2->quantity_on_hand);

        // Step 10: Create a bill for the purchase order
        $bill = Bill::create([
            'tenant_id' => $this->tenant->id,
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'supplier_id' => $supplier->supplier_id,
            'bill_number' => 'BILL-IMPORT-001',
            'bill_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'subtotal' => 5500.00,
            'tax_amount' => 500.00,
            'total_amount' => 6000.00, // Including landed costs
            'amount_paid' => 0.00,
            'status' => 'Awaiting Payment',
            'created_by_user_id' => $this->user->user_id,
        ]);

        // Step 11: Create partial payments for the bill
        $payment1 = Payment::create([
            'tenant_id' => $this->tenant->id,
            'payable_type' => Bill::class,
            'payable_id' => $bill->bill_id,
            'payment_date' => Carbon::now(),
            'amount' => 3000.00,
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'PAY-IMPORT-001',
            'created_by_user_id' => $this->user->user_id,
        ]);

        $payment2 = Payment::create([
            'tenant_id' => $this->tenant->id,
            'payable_type' => Bill::class,
            'payable_id' => $bill->bill_id,
            'payment_date' => Carbon::now()->addDays(15),
            'amount' => 3000.00,
            'payment_method' => 'Credit Card',
            'reference_number' => 'PAY-IMPORT-002',
            'created_by_user_id' => $this->user->user_id,
        ]);

        // Step 12: Verify the complete import flow
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Import Supplier Co.',
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'purchase_order_number' => 'PO-IMPORT-001',
            'status' => 'Confirmed',
        ]);

        $this->assertDatabaseHas('landed_costs', [
            'costable_type' => PurchaseOrder::class,
            'costable_id' => $purchaseOrder->purchase_order_id,
            'description' => 'Freight charges',
            'amount' => 800.00,
        ]);

        $this->assertDatabaseHas('bills', [
            'bill_number' => 'BILL-IMPORT-001',
            'total_amount' => 6000.00,
        ]);

        $this->assertDatabaseHas('payments', [
            'reference_number' => 'PAY-IMPORT-001',
            'amount' => 3000.00,
        ]);

        $this->assertDatabaseHas('payments', [
            'reference_number' => 'PAY-IMPORT-002',
            'amount' => 3000.00,
        ]);

        // Step 13: Verify final product costs include landed costs
        $this->assertEquals(414.00, $product1->cost);
        $this->assertEquals(138.00, $product2->cost);
    }

    #[Test]
    public function import_with_multiple_landed_cost_types()
    {
        // Create supplier and products
        $supplier = Supplier::factory()->forTenant($this->tenant)->create();
        $product = Product::factory()->forTenant($this->tenant)->create([
            'cost' => 100.00,
            'quantity_on_hand' => 0,
        ]);

        // Create purchase order
        $purchaseOrder = PurchaseOrder::create([
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $supplier->supplier_id,
            'purchase_order_number' => 'PO-MULTI-001',
            'order_date' => Carbon::now(),
            'expected_delivery_date' => Carbon::now()->addDays(30),
            'type' => 'Import',
            'status' => 'Confirmed',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'created_by_user_id' => $this->user->user_id,
        ]);

        // Create item
        $item = PurchaseOrderItem::create([
            'tenant_id' => $this->tenant->id,
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => $product->product_id,
            'item_name' => $product->name,
            'quantity' => 5,
            'unit_price' => 200.00,
            'item_total' => 1000.00,
        ]);

        // Add various types of landed costs
        $costTypes = [
            'Freight charges' => 150.00,
            'Customs duties' => 100.00,
            'Insurance' => 50.00,
            'Bank transfer fees' => 25.00,
            'Handling charges' => 75.00,
            'Customs broker fees' => 100.00,
            'Port charges' => 50.00,
            'Documentation fees' => 25.00,
        ];

        foreach ($costTypes as $description => $amount) {
            LandedCost::create([
                'tenant_id' => $this->tenant->id,
                'costable_type' => PurchaseOrder::class,
                'costable_id' => $purchaseOrder->purchase_order_id,
                'description' => $description,
                'amount' => $amount,
            ]);
        }

        // Apportion costs
        $landedCostService = new LandedCostService();
        $landedCostService->apportionCosts($purchaseOrder);

        // Verify landed cost per unit
        $item->refresh();
        $totalLandedCosts = array_sum($costTypes); // 575
        $expectedLandedCostPerUnit = $totalLandedCosts / 5; // 115 per unit
        $this->assertEquals(number_format($expectedLandedCostPerUnit, 4), $item->landed_cost_per_unit);

        // Update product cost
        $product->receiveStock(5, $item->unit_price + $item->landed_cost_per_unit);
        $product->refresh();

        // Verify final product cost includes landed costs
        $expectedCost = $item->unit_price + $item->landed_cost_per_unit; // 200 + 115 = 315
        $this->assertEquals($expectedCost, $product->cost);
    }
} 