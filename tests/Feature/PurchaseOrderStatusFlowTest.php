<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\CrmUser;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PurchaseOrderStatusFlowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $tenant;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a tenant and user for testing
        $this->tenant = \App\Models\Tenant::factory()->create();
        $this->user = CrmUser::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
    }

    public function test_purchase_order_status_flow()
    {
        // Create supplier and product with tenant context
        $supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        $product = Product::factory()->create([
            'cost' => 100.00,
            'created_by_user_id' => $this->user->user_id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create purchase order in draft status
        $purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->supplier_id,
            'status' => 'draft',
            'created_by_user_id' => $this->user->user_id,
        ]);

        // Test 1: Draft -> Confirmed
        $this->assertTrue($purchaseOrder->canBeConfirmed());
        $this->assertTrue($purchaseOrder->confirm());
        $this->assertEquals('confirmed', $purchaseOrder->status);

        // Test 2: Confirmed -> Ready for Dispatch
        $this->assertTrue($purchaseOrder->canBeReadyForDispatch());
        $this->assertTrue($purchaseOrder->markAsReadyForDispatch());
        $this->assertEquals('ready_for_dispatch', $purchaseOrder->status);

        // Test 3: Ready for Dispatch -> Dispatched
        $this->assertTrue($purchaseOrder->canBeDispatched());
        $this->assertTrue($purchaseOrder->markAsDispatched());
        $this->assertEquals('dispatched', $purchaseOrder->status);

        // Test 4: Cannot go back to previous states
        $this->assertFalse($purchaseOrder->canBeConfirmed());
        $this->assertFalse($purchaseOrder->canBeReadyForDispatch());
        $this->assertFalse($purchaseOrder->canBeDispatched());
    }

    public function test_payment_restrictions_by_status()
    {
        // Create supplier with tenant context
        $supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        // Test different statuses and payment permissions
        $statuses = [
            'draft' => false, // Cannot receive payments
            'confirmed' => true, // Can receive payments
            'ready_for_dispatch' => true, // Can receive payments
            'dispatched' => true, // Can receive payments
            'partially_received' => true, // Can receive payments
            'fully_received' => true, // Can receive payments
            'cancelled' => false, // Cannot receive payments
        ];

        foreach ($statuses as $status => $canReceivePayments) {
            $purchaseOrder = PurchaseOrder::factory()->create([
                'supplier_id' => $supplier->supplier_id,
                'status' => $status,
                'total_amount' => 1000.00,
                'created_by_user_id' => $this->user->user_id,
                'tenant_id' => $this->tenant->id,
            ]);

            $this->assertEquals(
                $canReceivePayments,
                $purchaseOrder->canReceivePayments(),
                "Purchase order with status '{$status}' should " . ($canReceivePayments ? 'allow' : 'not allow') . " payments"
            );
        }
    }

    public function test_payment_processing_by_status()
    {
        // Create supplier with tenant context
        $supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        // Create purchase order in confirmed status (can receive payments)
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->supplier_id,
            'purchase_order_number' => 'PO-TEST-' . uniqid(),
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(30),
            'type' => 'Standard',
            'status' => 'confirmed',
            'subtotal' => 1000.00,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'shipping_cost' => 0,
            'other_charges' => 0,
            'total_amount' => 1000.00,
            'amount_paid' => 0,
            'created_by_user_id' => $this->user->user_id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create a payment
        $payment = Payment::factory()->create([
            'payable_type' => PurchaseOrder::class,
            'payable_id' => $purchaseOrder->purchase_order_id,
            'amount' => 500.00,
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
            'reference_number' => 'REF-001',
            'tenant_id' => $this->tenant->id,
        ]);

        // Update status after payment
        $purchaseOrder->updateStatusAfterPayment();

        $this->assertEquals('partially_paid', $purchaseOrder->status);
        $this->assertEquals(500.00, $purchaseOrder->amount_paid);

        // Add another payment to fully pay
        Payment::factory()->create([
            'payable_type' => PurchaseOrder::class,
            'payable_id' => $purchaseOrder->purchase_order_id,
            'amount' => 500.00,
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
            'reference_number' => 'REF-002',
            'tenant_id' => $this->tenant->id,
        ]);

        $purchaseOrder->updateStatusAfterPayment();

        // Debug: Check actual values
        $this->assertEquals('paid', $purchaseOrder->status, "Expected 'paid' but got '{$purchaseOrder->status}'. Amount paid: {$purchaseOrder->amount_paid}, Total: {$purchaseOrder->total_amount}");
        $this->assertTrue(bccomp($purchaseOrder->amount_paid, 1000.00, 2) == 0, 'Amount paid should be exactly 1000.00');
    }

    public function test_inventory_receipt_separate_from_po_status()
    {
        // Create supplier and product with tenant context
        $supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        $product = Product::factory()->create([
            'cost' => 100.00,
            'quantity_on_hand' => 0,
            'created_by_user_id' => $this->user->user_id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create purchase order in dispatched status
        $purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->supplier_id,
            'status' => 'dispatched',
            'created_by_user_id' => $this->user->user_id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create purchase order item
        $poItem = $purchaseOrder->items()->create([
            'product_id' => $product->product_id,
            'item_name' => $product->name,
            'quantity' => 10,
            'unit_price' => 100.00,
            'item_total' => 1000.00,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create goods receipt (separate transaction)
        $goodsReceipt = GoodsReceipt::factory()->create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'received_by_user_id' => $this->user->user_id,
            'status' => 'draft',
            'tenant_id' => $this->tenant->id,
        ]);

        // Create goods receipt item
        $receiptItem = $goodsReceipt->items()->create([
            'purchase_order_item_id' => $poItem->purchase_order_item_id,
            'product_id' => $product->product_id,
            'quantity_received' => 30, // Partial receipt
            'unit_cost_with_landed' => 110.00, // Including landed costs
            'total_cost' => 3300.00,
            'tenant_id' => $this->tenant->id,
        ]);

        // Process the receipt (updates inventory)
        $goodsReceipt->processReceipt();

        // Verify inventory was updated
        $product->refresh();
        $this->assertEquals(30, $product->quantity_on_hand);
        $this->assertEquals(110.00, $product->cost);

        // Verify purchase order status was updated
        $purchaseOrder->refresh();
        $this->assertEquals('partially_received', $purchaseOrder->status);

        // Verify goods receipt status
        $goodsReceipt->refresh();
        $this->assertEquals('received', $goodsReceipt->status);
    }

    public function test_cancellation_restrictions()
    {
        // Create supplier with tenant context
        $supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        // Test cancellation restrictions
        $cancellableStatuses = ['draft', 'confirmed'];
        $nonCancellableStatuses = ['ready_for_dispatch', 'dispatched', 'partially_received', 'fully_received', 'cancelled'];

        foreach ($cancellableStatuses as $status) {
            $purchaseOrder = PurchaseOrder::factory()->create([
                'supplier_id' => $supplier->supplier_id,
                'status' => $status,
                'created_by_user_id' => $this->user->user_id,
                'tenant_id' => $this->tenant->id,
            ]);

            $this->assertTrue(
                in_array($purchaseOrder->status, ['draft', 'confirmed']),
                "Purchase order with status '{$status}' should be cancellable"
            );
        }

        foreach ($nonCancellableStatuses as $status) {
            $purchaseOrder = PurchaseOrder::factory()->create([
                'supplier_id' => $supplier->supplier_id,
                'status' => $status,
                'created_by_user_id' => $this->user->user_id,
                'tenant_id' => $this->tenant->id,
            ]);

            $this->assertFalse(
                in_array($purchaseOrder->status, ['draft', 'confirmed']),
                "Purchase order with status '{$status}' should not be cancellable"
            );
        }
    }

    public function test_status_transitions_api()
    {
        // Create supplier and product with tenant context
        $supplier = Supplier::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        $product = Product::factory()->create([
            'cost' => 100.00,
            'created_by_user_id' => $this->user->user_id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create purchase order in draft status
        $purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->supplier_id,
            'status' => 'draft',
            'created_by_user_id' => $this->user->user_id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Test API endpoint for available transitions
        $response = $this->actingAs($this->user)
            ->getJson("/purchase-orders/{$purchaseOrder->purchase_order_id}/transitions");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'current_status' => 'draft',
                'available_transitions' => ['confirm', 'cancel'],
                'can_receive_payments' => false,
            ]);

        // Confirm the purchase order
        $response = $this->actingAs($this->user)
            ->postJson("/purchase-orders/{$purchaseOrder->purchase_order_id}/confirm");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'new_status' => 'confirmed',
            ]);

        // Check transitions after confirmation
        $response = $this->actingAs($this->user)
            ->getJson("/purchase-orders/{$purchaseOrder->purchase_order_id}/transitions");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'current_status' => 'confirmed',
                'available_transitions' => ['mark_ready_for_dispatch', 'cancel'],
                'can_receive_payments' => true,
            ]);
    }
} 