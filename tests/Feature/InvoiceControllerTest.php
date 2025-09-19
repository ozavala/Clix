<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Permission;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class InvoiceControllerTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SettingsTableSeeder::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        // Asignar permisos necesarios para gestión de facturas
        // Esto es requerido por la lógica de autorización en el controlador de facturas
        $this->givePermission($this->user, [
            'view-invoices',
            'create-invoices',
            'edit-invoices',
            'delete-invoices'
        ]);
    }

    #[Test]
    public function it_can_display_invoices_index()
    {
        Invoice::factory()->count(3)->create();

        $response = $this->get(route('invoices.index'));

        $response->assertOk();
        $response->assertViewIs('invoices.index');
        $response->assertViewHas('invoices');
    }

    #[Test]
    public function it_can_search_invoices()
    {
        Invoice::factory()->create(['invoice_number' => 'INV-001']);
        Invoice::factory()->create(['invoice_number' => 'INV-002']);

        $response = $this->get(route('invoices.index', ['search' => 'INV-001']));

        $response->assertOk();
        $response->assertViewHas('invoices');
        $response->assertSee('INV-001');
        $response->assertDontSee('INV-002');
    }

    #[Test]
    public function it_can_display_create_invoice_form()
    {
        $response = $this->get(route('invoices.create'));

        $response->assertOk();
        $response->assertViewIs('invoices.create');
        $response->assertViewHas('customers');
        $response->assertViewHas('products');
    }

    #[Test]
    public function it_can_store_a_new_invoice()
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->customer_id,
            'status' => 'Completed'
        ]);
        $product = Product::factory()->create();
        
        $invoiceData = [
            'order_id' => $order->order_id,
            'customer_id' => $customer->customer_id,
            'invoice_number' => 'INV-001',
            'invoice_date' => '2024-01-15',
            'due_date' => '2024-01-30',
            'status' => 'Draft',
            'notes' => 'Test invoice notes',
            'tax_percentage' => 10.0,
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'item_name' => $product->name,
                    'quantity' => 2,
                    'unit_price' => 50.00
                ]
            ]
        ];

        $response = $this->post(route('invoices.store'), $invoiceData);

        $response->assertRedirect(route('invoices.index'));
        $response->assertSessionHas('success', 'Invoice created successfully.');

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $customer->customer_id,
            'invoice_date' => '2024-01-15 00:00:00',
            'due_date' => '2024-01-30 00:00:00',
            'status' => 'Draft',
            'notes' => 'Test invoice notes',
        ]);

        $invoice = Invoice::where('customer_id', $customer->customer_id)->first();
        $this->assertNotNull($invoice);
        $this->assertCount(1, $invoice->items);
        $this->assertEquals(2, $invoice->items->first()->quantity);
        $this->assertEquals(50.00, $invoice->items->first()->unit_price);
    }

    #[Test]
    public function it_can_display_invoice_details()
    {
        $invoice = Invoice::factory()->create();

        $response = $this->get(route('invoices.show', $invoice));

        $response->assertOk();
        $response->assertViewIs('invoices.show');
        $response->assertViewHas('invoice');
        $response->assertSee($invoice->invoice_number);
    }

    #[Test]
    public function it_can_display_edit_invoice_form()
    {
        $invoice = Invoice::factory()->create();

        $response = $this->get(route('invoices.edit', $invoice));

        $response->assertOk();
        $response->assertViewIs('invoices.edit');
        $response->assertViewHas('invoice');
        $response->assertViewHas('customers');
        $response->assertViewHas('products');
    }

    #[Test]
    public function it_can_update_invoice()
    {
        $invoice = Invoice::factory()->create();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->customer_id,
            'status' => 'Completed'
        ]);
        $product = Product::factory()->create();
        
        $updateData = [
            'order_id' => $order->order_id,
            'customer_id' => $customer->customer_id,
            'invoice_number' => 'INV-002',
            'invoice_date' => '2024-02-15',
            'due_date' => '2024-02-28',
            'status' => 'Sent',
            'notes' => 'Updated invoice notes',
            'tax_percentage' => 15.0,
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'item_name' => $product->name,
                    'quantity' => 3,
                    'unit_price' => 75.00
                ]
            ]
        ];

        $response = $this->put(route('invoices.update', $invoice), $updateData);

        $response->assertRedirect(route('invoices.index'));
        $response->assertSessionHas('success', 'Invoice updated successfully.');

        $this->assertDatabaseHas('invoices', [
            'invoice_id' => $invoice->invoice_id,
            'customer_id' => $customer->customer_id,
            'status' => 'Sent',
            'notes' => 'Updated invoice notes',
        ]);

        $invoice->refresh();
        $this->assertCount(1, $invoice->items);
        $this->assertEquals(3, $invoice->items->first()->quantity);
        $this->assertEquals(75.00, $invoice->items->first()->unit_price);
    }

    #[Test]
    public function it_can_delete_invoice()
    {
        $invoice = Invoice::factory()->create();

        $response = $this->delete(route('invoices.destroy', $invoice));

        $response->assertRedirect(route('invoices.index'));
        $response->assertSessionHas('success', 'Invoice deleted successfully.');

        $this->assertSoftDeleted('invoices', ['invoice_id' => $invoice->invoice_id]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_invoice()
    {
        $response = $this->post(route('invoices.store'), []);

        // Solo los campos realmente requeridos
        $response->assertSessionHasErrors(['customer_id', 'invoice_date', 'due_date', 'status', 'items']);
    }

    #[Test]
    public function it_validates_invoice_date_format()
    {
        $customer = Customer::factory()->create();
        
        $invoiceData = [
            'customer_id' => $customer->customer_id,
            'invoice_date' => 'invalid-date',
            'status' => 'Draft',
        ];

        $response = $this->post(route('invoices.store'), $invoiceData);

        $response->assertSessionHasErrors(['invoice_date']);
    }

    #[Test]
    public function it_validates_invoice_items_are_required()
    {
        $customer = Customer::factory()->create();
        
        $invoiceData = [
            'customer_id' => $customer->customer_id,
            'invoice_date' => '2024-01-15',
            'status' => 'Draft',
            'items' => []
        ];

        $response = $this->post(route('invoices.store'), $invoiceData);

        $response->assertSessionHasErrors(['items']);
    }

    #[Test]
    public function it_can_handle_multiple_invoice_items()
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->customer_id,
            'status' => 'Completed'
        ]);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        
        $invoiceData = [
            'order_id' => $order->order_id,
            'customer_id' => $customer->customer_id,
            'invoice_number' => 'INV-003',
            'invoice_date' => '2024-01-15',
            'due_date' => '2024-01-30',
            'status' => 'Draft',
            'notes' => 'Invoice with multiple items',
            'tax_percentage' => 10.0,
            'items' => [
                [
                    'product_id' => $product1->product_id,
                    'item_name' => $product1->name,
                    'quantity' => 2,
                    'unit_price' => 50.00
                ],
                [
                    'product_id' => $product2->product_id,
                    'item_name' => $product2->name,
                    'quantity' => 1,
                    'unit_price' => 100.00
                ]
            ]
        ];

        $response = $this->post(route('invoices.store'), $invoiceData);

        $response->assertRedirect(route('invoices.index'));

        $invoice = Invoice::where('customer_id', $customer->customer_id)->first();
        $this->assertNotNull($invoice);
        $this->assertCount(2, $invoice->items);
        
        // Check that both items are properly stored
        $itemQuantities = $invoice->items->pluck('quantity')->toArray();
        $this->assertContains(2, $itemQuantities);
        $this->assertContains(1, $itemQuantities);
    }

    #[Test]
    public function it_can_calculate_invoice_totals_with_tax()
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->customer_id,
            'status' => 'Completed'
        ]);
        $product = Product::factory()->create();
        
        $invoiceData = [
            'order_id' => $order->order_id,
            'customer_id' => $customer->customer_id,
            'invoice_number' => 'INV-004',
            'invoice_date' => '2024-01-15',
            'due_date' => '2024-01-30',
            'status' => 'Draft',
            'notes' => 'Invoice with tax calculation',
            'tax_percentage' => 15.0,
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'item_name' => $product->name,
                    'quantity' => 2,
                    'unit_price' => 50.00
                ]
            ]
        ];

        $response = $this->post(route('invoices.store'), $invoiceData);

        $response->assertRedirect(route('invoices.index'));

        $invoice = Invoice::where('customer_id', $customer->customer_id)->first();
        $this->assertNotNull($invoice);
        
        // Calculate expected totals
        $subtotal = 2 * 50.00; // quantity * unit_price
        $tax_amount = $subtotal * 0.15; // 15% tax
        $total = $subtotal + $tax_amount;
        
        $this->assertEquals($subtotal, $invoice->subtotal);
        $this->assertEquals($tax_amount, $invoice->tax_amount);
        $this->assertEquals($total, $invoice->total_amount);
    }

    #[Test]
    public function it_can_create_invoice_from_order()
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->customer_id,
            'status' => 'Completed'
        ]);
        
        $response = $this->get(route('invoices.create', ['order_id' => $order->order_id]));

        $response->assertOk();
        $response->assertViewIs('invoices.create');
        $response->assertViewHas('order');
        $response->assertViewHas('customer');
    }

    #[Test]
    public function it_can_send_invoice_email()
    {
        $invoice = Invoice::factory()->create(['status' => 'Draft']);

        $response = $this->post(route('invoices.send', $invoice));

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('success', 'Invoice sent successfully.');

        $invoice->refresh();
        $this->assertEquals('Sent', $invoice->status);
    }
} 