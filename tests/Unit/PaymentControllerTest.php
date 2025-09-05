<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Bill;
use App\Models\CrmUser;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected CrmUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user and act as that user for all tests in this class
        $this->user = CrmUser::factory()->create();
        $this->actingAs($this->user);
    }

    #[Test]
    public function it_can_store_a_payment_for_a_bill_and_update_status()
    {
        // Arrange
        $bill = Bill::factory()->create(['total_amount' => 500.00, 'amount_paid' => 100.00]);
        $paymentAmount = 200.00;

        $paymentData = [
            'payable_id' => $bill->bill_id,
            'payable_type' => Bill::class,
            'amount' => $paymentAmount,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Bank Transfer',
        ];

        // Act
        $response = $this->post(route('bills.payments.store', $bill), $paymentData);

        // Assert
        $this->assertDatabaseHas('payments', [
            'payable_id' => $bill->bill_id,
            'payable_type' => Bill::class,
            'amount' => $paymentAmount,
            'created_by_user_id' => $this->user->getKey(),
        ]);

        $bill->refresh();
        $this->assertEquals(200.00, $bill->amount_paid); // Se actualiza automáticamente
        $this->assertEquals('Partially Paid', $bill->status);

        // Assert a final payment changes status to 'Paid'
        $this->post(route('bills.payments.store', $bill), array_merge($paymentData, ['amount' => 300.00]));
        $bill->refresh();
        $this->assertEquals(500.00, $bill->amount_paid); // Se actualiza automáticamente
        $this->assertEquals('Paid', $bill->status);

        $response->assertRedirect(route('bills.show', $bill->bill_id));
        $response->assertSessionHas('success', 'Payment recorded successfully.');
    }

    #[Test]
    public function it_can_store_a_payment_for_a_purchase_order_and_update_po_status()
    {
        // Arrange
        $purchaseOrder = new PurchaseOrder([
            'supplier_id' => Supplier::factory()->create()->supplier_id,
            'purchase_order_number' => 'PO-TEST-001',
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(30),
            'type' => 'Standard',
            'status' => 'Confirmed',
            'subtotal' => 500.00,
            'discount_amount' => 0.00,
            'tax_percentage' => 0.00,
            'tax_amount' => 0.00,
            'shipping_cost' => 0.00,
            'other_charges' => 0.00,
            'total_amount' => 500.00,
            'amount_paid' => 100.00,
            'created_by_user_id' => $this->user->getKey(),
        ]);
        $purchaseOrder->save();
        
        $paymentAmount = 200.00;

        $paymentData = [
            'payable_id' => $purchaseOrder->purchase_order_id,
            'payable_type' => PurchaseOrder::class,
            'amount' => $paymentAmount,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Bank Transfer',
        ];

        // Act
        $response = $this->post(route('purchase-orders.payments.store', $purchaseOrder), $paymentData);

        // Assert
        $this->assertDatabaseHas('payments', [
            'payable_id' => $purchaseOrder->purchase_order_id,
            'payable_type' => PurchaseOrder::class,
            'amount' => $paymentAmount,
            'created_by_user_id' => $this->user->getKey(),
        ]);

        $purchaseOrder->refresh();
        $this->assertEquals(200.00, $purchaseOrder->amount_paid); // Se actualiza automáticamente
        $this->assertEquals('partially_paid', $purchaseOrder->status);

        // Assert a final payment changes status to 'Paid'
        $this->post(route('purchase-orders.payments.store', $purchaseOrder), array_merge($paymentData, ['amount' => 300.00]));
        $purchaseOrder->refresh();
        $this->assertEquals(500.00, $purchaseOrder->amount_paid); // Se actualiza automáticamente
        $this->assertEquals('paid', $purchaseOrder->status);

        $response->assertRedirect(route('purchase-orders.show', $purchaseOrder->purchase_order_id));
        $response->assertSessionHas('success', 'Payment recorded successfully.');
    }

    #[Test]
    public function it_returns_an_error_if_payment_exceeds_bill_amount_due()
    {
        // Arrange
        $bill = Bill::factory()->create(['total_amount' => 500, 'amount_paid' => 400]); // 100 due
        $paymentAmount = 100.01;

        $paymentData = [
            'payable_id' => $bill->bill_id,
            'payable_type' => Bill::class,
            'amount' => $paymentAmount,
            'payment_date' => now()->toDateString(),
        ];

        // Act
        $response = $this->post(route('bills.payments.store', $bill), $paymentData);

        // Assert
        $this->assertDatabaseMissing('payments', ['amount' => $paymentAmount]);
        $bill->refresh();
        $this->assertEquals(400, $bill->amount_paid); // Unchanged
        $response->assertSessionHas('error', 'Payment amount cannot exceed the amount due.');
    }

    #[Test]
    public function it_returns_an_error_if_payment_exceeds_purchase_order_amount_due()
    {
        // Arrange
        $purchaseOrder = new PurchaseOrder([
            'supplier_id' => Supplier::factory()->create()->supplier_id,
            'purchase_order_number' => 'PO-TEST-002',
            'order_date' => now(),
            'expected_delivery_date' => now()->addDays(30),
            'type' => 'Standard',
            'status' => 'Confirmed',
            'subtotal' => 500.00,
            'discount_amount' => 0.00,
            'tax_percentage' => 0.00,
            'tax_amount' => 0.00,
            'shipping_cost' => 0.00,
            'other_charges' => 0.00,
            'total_amount' => 500.00,
            'amount_paid' => 400.00,
            'created_by_user_id' => $this->user->getKey(),
        ]);
        $purchaseOrder->save();
        
        $paymentAmount = 100.01;

        $paymentData = [
            'payable_id' => $purchaseOrder->purchase_order_id,
            'payable_type' => PurchaseOrder::class,
            'amount' => $paymentAmount,
            'payment_date' => now()->toDateString(),
        ];

        // Act
        $response = $this->post(route('purchase-orders.payments.store', $purchaseOrder), $paymentData);

        // Assert
        $this->assertDatabaseMissing('payments', ['amount' => $paymentAmount]);
        $purchaseOrder->refresh();
        $this->assertEquals(400, $purchaseOrder->amount_paid); // Unchanged
        $response->assertSessionHas('error', 'Payment amount cannot exceed the amount due.');
    }

    #[Test]
    public function it_can_delete_a_payment_for_a_bill()
    {
        // Arrange
        $bill = Bill::factory()->create(['total_amount' => 1000, 'amount_paid' => 0]);
        $payment = Payment::factory()->create([
            'payable_id' => $bill->bill_id,
            'payable_type' => Bill::class,
            'amount' => 300,
        ]);
        $bill->update(['amount_paid' => 300, 'status' => 'Partially Paid']);

        // Act
        $response = $this->delete(route('payments.destroy', $payment));

        // Assert
        $this->assertSoftDeleted($payment);
        $bill->refresh();
        $this->assertEquals(0.00, $bill->amount_paid); // Se actualiza automáticamente
        $this->assertEquals('Awaiting Payment', $bill->status);
        $response->assertRedirect(route('bills.show', $bill->bill_id));
    }

    #[Test]
    public function it_can_delete_a_payment_for_a_purchase_order()
    {
        // Arrange
        $purchaseOrder = PurchaseOrder::factory()->create(['total_amount' => 1000, 'amount_paid' => 0, 'status' => 'Confirmed']);
        $payment = Payment::factory()->create([
            'payable_id' => $purchaseOrder->purchase_order_id,
            'payable_type' => PurchaseOrder::class,
            'amount' => 300,
        ]);
        $purchaseOrder->update(['amount_paid' => 300, 'status' => 'Partially Paid']);

        // Act
        $response = $this->delete(route('payments.destroy', $payment));

        // Assert
        $this->assertSoftDeleted($payment);
        $purchaseOrder->refresh();
        $this->assertEquals(0.00, $purchaseOrder->amount_paid); // Se actualiza automáticamente
        $this->assertEquals('confirmed', $purchaseOrder->status);
        $response->assertRedirect(route('purchase-orders.show', $purchaseOrder->purchase_order_id));
    }


    #[Test]
    public function it_creates_journal_entries_for_a_bill_payment()
    {
        // Arrange
        Account::create(['code' => '2101', 'name' => 'Accounts Payable', 'type' => 'liability']);
        Account::create(['code' => '1101', 'name' => 'Bank', 'type' => 'asset']);
        $bill = Bill::factory()->create();
        $paymentAmount = 150.00;
        $paymentData = [
            'payable_id' => $bill->bill_id,
            'payable_type' => Bill::class,
            'amount' => $paymentAmount,
            'payment_date' => now()->toDateString(),
        ];

        // Act
        $this->post(route('bills.payments.store', $bill), $paymentData);

        // Assert
        $this->assertDatabaseHas('journal_entries', [
            'transaction_type' => 'Payment',
            'description' => "Payment for Bill #" . $bill->bill_number,
        ]);

        $journalEntry = JournalEntry::latest('journal_entry_id')->first();
        $supplierLegalId = $bill->supplier->legal_id ?? 'N/A';
        $companyLegalId = \App\Models\Setting::where('key', 'company_legal_id')->first()?->value ?? 'N/A';
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'account_name' => 'Accounts Payable (' . $supplierLegalId . ')',
            'debit_amount' => number_format($paymentAmount, 2, '.', ''),
            'entity_id' => $bill->supplier_id,
            'description' => 'Supplier accounts payable',
        ]);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'account_name' => 'Bank (' . $companyLegalId . ')',
            'credit_amount' => number_format($paymentAmount, 2, '.', ''),
            'description' => 'Bank account of main company',
        ]);
    }

    #[Test]
    public function it_creates_journal_entries_for_a_purchase_order_payment()
    {
        // Arrange
        Account::create(['code' => '2101', 'name' => 'Accounts Payable', 'type' => 'liability']);
        Account::create(['code' => '1101', 'name' => 'Bank', 'type' => 'asset']);
        $purchaseOrder = PurchaseOrder::factory()->create();
        $paymentAmount = 250.00;
        $paymentData = [
            'payable_id' => $purchaseOrder->purchase_order_id,
            'payable_type' => PurchaseOrder::class,
            'amount' => $paymentAmount,
            'payment_date' => now()->toDateString(),
        ];

        // Act
        $this->post(route('purchase-orders.payments.store', $purchaseOrder), $paymentData);

        // Assert
        $this->assertDatabaseHas('journal_entries', [
            'transaction_type' => 'Payment',
            'description' => "Payment for PurchaseOrder #" . $purchaseOrder->purchase_order_number,
        ]);

        $journalEntry = JournalEntry::latest('journal_entry_id')->first();
        $supplierLegalId = $purchaseOrder->supplier->legal_id ?? 'N/A';
        $companyLegalId = \App\Models\Setting::where('key', 'company_legal_id')->first()?->value ?? 'N/A';
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'account_name' => 'Accounts Payable (' . $supplierLegalId . ')',
            'debit_amount' => number_format($paymentAmount, 2, '.', ''),
            'entity_id' => $purchaseOrder->supplier_id,
            'description' => 'Supplier accounts payable',
        ]);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'account_name' => 'Bank (' . $companyLegalId . ')',
            'credit_amount' => number_format($paymentAmount, 2, '.', ''),
            'description' => 'Bank account of main company',
        ]);
    }

    #[Test]
    public function it_creates_journal_entries_with_description_and_legal_id_for_bill_payment()
    {
        // Arrange
        Account::create(['code' => '2101', 'name' => 'Accounts Payable', 'type' => 'liability']);
        Account::create(['code' => '1101', 'name' => 'Bank', 'type' => 'asset']);
        $bill = Bill::factory()->create();
        $paymentAmount = 150.00;
        $paymentData = [
            'payable_id' => $bill->bill_id,
            'payable_type' => Bill::class,
            'amount' => $paymentAmount,
            'payment_date' => now()->toDateString(),
        ];

        // Act
        $this->post(route('bills.payments.store', $bill), $paymentData);

        // Assert
        $journalEntry = JournalEntry::latest('journal_entry_id')->first();
        
        // Verificar que las líneas del asiento contable tengan description
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'description' => 'Supplier accounts payable',
        ]);
        
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'description' => 'Bank account of main company',
        ]);
        
        // Verificar que account_name contenga legal_id
        $supplierLegalId = $bill->supplier->legal_id ?? 'N/A';
        $companyLegalId = \App\Models\Setting::where('key', 'company_legal_id')->first()?->value ?? 'N/A';
        
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'account_name' => 'Accounts Payable (' . $supplierLegalId . ')',
        ]);
        
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'account_name' => 'Bank (' . $companyLegalId . ')',
        ]);
    }

    #[Test]
    public function it_creates_journal_entries_with_description_and_legal_id_for_invoice_payment()
    {
        // Arrange
        Account::create(['code' => '2102', 'name' => 'Accounts Receivable', 'type' => 'asset']);
        Account::create(['code' => '1101', 'name' => 'Bank', 'type' => 'asset']);
        $invoice = \App\Models\Invoice::factory()->create();
        $paymentAmount = 200.00;
        $paymentData = [
            'payable_id' => $invoice->invoice_id,
            'payable_type' => \App\Models\Invoice::class,
            'amount' => $paymentAmount,
            'payment_date' => now()->toDateString(),
        ];

        // Act
        $this->post(route('invoices.payments.store', $invoice), $paymentData);

        // Assert
        $journalEntry = JournalEntry::latest('journal_entry_id')->first();
        
        // Verificar que las líneas del asiento contable tengan description
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'description' => 'Customer accounts receivable',
        ]);
        
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'description' => 'Bank account of main company',
        ]);
        
        // Verificar que account_name contenga legal_id
        $customerLegalId = $invoice->customer->legal_id ?? 'N/A';
        $companyLegalId = \App\Models\Setting::where('key', 'company_legal_id')->first()?->value ?? 'N/A';
        
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'account_name' => 'Accounts Receivable (' . $customerLegalId . ')',
        ]);
        
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'account_name' => 'Bank (' . $companyLegalId . ')',
        ]);
    }
}