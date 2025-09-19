<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\PurchaseOrder;

class BillControllerTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

     #[Test]
    public function it_can_store_a_new_bill_from_a_purchase_order()
    {
        // 1. Arrange
        // Create a Purchase Order with two items
        $purchaseOrder = PurchaseOrder::factory()->hasItems(2)->create();
        $poItem1 = $purchaseOrder->items[0];
        $poItem2 = $purchaseOrder->items[1];

        // Prepare the data for the store request, mimicking the form submission
        $postData = [
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'supplier_id' => $purchaseOrder->supplier_id,
            'bill_number' => 'INV-FROM-SUPPLIER-001',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_amount' => 25.00,
            'notes' => 'Bill created from PO.',
            'items' => [
                [
                    'purchase_order_item_id' => $poItem1->purchase_order_item_id,
                    'product_id' => $poItem1->product_id,
                    'item_name' => $poItem1->item_name,
                    'item_description' => $poItem1->item_description,
                    'quantity' => $poItem1->quantity,
                    'unit_price' => $poItem1->unit_price,
                ],
                [
                    'purchase_order_item_id' => $poItem2->purchase_order_item_id,
                    'product_id' => $poItem2->product_id,
                    'item_name' => $poItem2->item_name,
                    'item_description' => $poItem2->item_description,
                    'quantity' => $poItem2->quantity,
                    'unit_price' => $poItem2->unit_price,
                ],
            ],
        ];

        // 2. Act
        $response = $this->post(route('bills.store'), $postData);

        // 3. Assert
        $this->assertDatabaseHas('bills', [
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'bill_number' => 'INV-FROM-SUPPLIER-001',
        ]);

        $bill = Bill::where('bill_number', 'INV-FROM-SUPPLIER-001')->first();
        $this->assertNotNull($bill);

        $response->assertRedirect(route('bills.show', $bill));
        $response->assertSessionHas('success', 'Bill created successfully.');

        $this->assertCount(2, $bill->items);
        $this->assertEquals($poItem1->item_name, $bill->items[0]->item_name);
    }


    #[Test]
    public function it_can_update_a_bill_and_its_items()
    {
        // 1. Arrange
        // Create an initial bill with two items
        $bill = Bill::factory()->create();
        $itemToUpdate = BillItem::factory()->create(['bill_id' => $bill->bill_id, 'quantity' => 2, 'unit_price' => 10.00]); // Total: 20.00
        $itemToDelete = BillItem::factory()->create(['bill_id' => $bill->bill_id, 'quantity' => 1, 'unit_price' => 50.00]); // Total: 50.00

        // Prepare the data for the update request
        $updatedData = [
            'supplier_id' => $bill->supplier_id,
            'bill_number' => 'UPDATED-BILL-123',
            'bill_date' => now()->subDay()->toDateString(),
            'due_date' => now()->addDays(20)->toDateString(),
            'tax_amount' => 15.00, // New tax amount
            'items' => [
                // Item 1: Update the quantity
                [
                    'bill_item_id' => $itemToUpdate->bill_item_id,
                    'item_name' => $itemToUpdate->item_name,
                    'quantity' => 5, // Changed from 2 to 5
                    'unit_price' => 10.00,
                ],
                // Item 2 (itemToDelete) is omitted, so it should be deleted.

                // Item 3: Add a new item
                [
                    'bill_item_id' => '', // No ID for new items
                    'item_name' => 'A Brand New Item',
                    'quantity' => 1,
                    'unit_price' => 75.00,
                ]
            ]
        ];

        // 2. Act
        $response = $this->put(route('bills.update', $bill), $updatedData);

        // 3. Assert
        // Assert redirection and session message
        $response->assertRedirect(route('bills.show', $bill->bill_id));
        $response->assertSessionHas('success', 'Bill updated successfully.');

        // Assert bill header data was updated
        $this->assertDatabaseHas('bills', [
            'bill_id' => $bill->bill_id,
            'bill_number' => 'UPDATED-BILL-123',
        ]);

        // Assert item changes in the database
        $this->assertDatabaseHas('bill_items', ['bill_item_id' => $itemToUpdate->bill_item_id, 'quantity' => 5]);
        $this->assertSoftDeleted('bill_items', ['bill_item_id' => $itemToDelete->bill_item_id]);
        $this->assertDatabaseHas('bill_items', ['bill_id' => $bill->bill_id, 'item_name' => 'A Brand New Item', 'unit_price' => 75.00]);

        // Assert totals were recalculated correctly
        // New subtotal = (5 * 10.00) + (1 * 75.00) = 50 + 75 = 125.00
        // New total = 125.00 (subtotal) + 15.00 (tax) = 140.00
        $this->assertDatabaseHas('bills', ['bill_id' => $bill->bill_id, 'subtotal' => 125.00, 'total_amount' => 140.00]);
    }
}