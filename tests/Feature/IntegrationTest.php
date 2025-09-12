<?php

namespace Tests\Feature;

use App\Models\CrmUser;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\UserRole;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected CrmUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions
        $permissions = [
            'view-leads', 'create-leads', 'edit-leads', 'delete-leads',
            'view-customers', 'create-customers', 'edit-customers', 'delete-customers',
            'view-invoices', 'create-invoices', 'edit-invoices', 'delete-invoices',
            'view-payments', 'create-payments', 'edit-payments', 'delete-payments',
        ];

        foreach ($permissions as $permissionName) {
            Permission::create([
                'name' => $permissionName,
                'description' => ucfirst(str_replace('-', ' ', $permissionName)),
            ]);
        }

        // Create a role with all permissions
        $role = UserRole::create([
            'name' => 'Integration Test Role',
            'description' => 'Role for integration testing',
        ]);

        $role->permissions()->attach(Permission::pluck('permission_id'));

        // Create user and assign role within current tenant
        $this->user = CrmUser::factory()->forTenant($this->tenant)->create();
        $this->user->roles()->attach($role->role_id);
        
        $this->actingAs($this->user, 'crm');
    }

    #[Test]
    public function complete_flow_from_lead_to_payment()
    {
        // Step 1: Create a lead
        $leadData = [
            'title' => 'John Doe - Premium Package Interest',
            'description' => 'Interested in our premium package',
            'value' => 1500.00,
            'status' => 'New',
            'source' => 'Website',
            'contact_name' => 'John Doe',
            'contact_email' => 'john.doe@example.com',
            'contact_phone' => '555-123-4567',
            'expected_close_date' => now()->addDays(30)->format('Y-m-d'),
        ];

        $response = $this->post(route('leads.store'), $leadData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $lead = Lead::where('contact_email', 'john.doe@example.com')->first();
        $this->assertNotNull($lead);
        $this->assertEquals('New', $lead->status);

        // Step 2: Update lead status to "Qualified"
        $response = $this->put(route('leads.update', $lead), array_merge($leadData, [
            'status' => 'Qualified',
            'description' => 'Lead has been qualified and shows strong interest',
        ]));
        $response->assertRedirect();
        
        $lead->refresh();
        $this->assertEquals('Qualified', $lead->status);

        // Step 3: Convert lead to customer
        $customerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone_number' => '555-123-4567',
            'company_name' => 'Doe Enterprises',
            'status' => 'Active',
            'type' => 'Company',
            'legal_id' => 'DOE-2024-001',
        ];

        $response = $this->post(route('customers.store'), $customerData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $customer = Customer::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Active', $customer->status);

        // Step 4: Create products for the invoice
        $product1 = Product::factory()->create([
            'name' => 'Premium Software License',
            'sku' => 'PSL-001',
            'price' => 299.99,
            'description' => 'Annual premium software license',
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Technical Support',
            'sku' => 'TS-001',
            'price' => 99.99,
            'description' => 'Monthly technical support package',
        ]);

        // Step 5: Create an invoice for the customer
        $invoiceData = [
            'customer_id' => $customer->customer_id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => 'Draft',
            'notes' => 'Invoice for premium package and support',
            'tax_percentage' => 10.0,
            'items' => [
                [
                    'product_id' => $product1->product_id,
                    'item_name' => $product1->name,
                    'item_description' => $product1->description,
                    'quantity' => 1,
                    'unit_price' => $product1->price,
                ],
                [
                    'product_id' => $product2->product_id,
                    'item_name' => $product2->name,
                    'item_description' => $product2->description,
                    'quantity' => 12, // 12 months of support
                    'unit_price' => $product2->price,
                ],
            ],
        ];

        $response = $this->post(route('invoices.store'), $invoiceData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Buscar la factura por customer_id y fecha, y obtener el número generado
        $invoice = Invoice::where('customer_id', $customer->customer_id)
            ->whereDate('invoice_date', now()->format('Y-m-d'))
            ->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('Draft', $invoice->status);
        $invoiceNumber = $invoice->invoice_number;

        // Calculate expected total: (299.99 + (99.99 * 12)) * 1.10 = 1,499.87
        $expectedSubtotal = 299.99 + (99.99 * 12); // 1,499.87
        $expectedTax = $expectedSubtotal * 0.10; // 149.99
        $expectedTotal = round($expectedSubtotal + $expectedTax, 2); // 1,649.86

        $this->assertTrue(bccomp($expectedTotal, $invoice->total_amount, 2) == 0, 
            "Expected total {$expectedTotal} but got {$invoice->total_amount}");

        // Step 6: Marcar la factura como 'Sent' usando la ruta de envío
        $response = $this->post(route('invoices.send', $invoice));
        $response->assertRedirect();
        $invoice->refresh();
        $this->assertEquals('Sent', $invoice->status);

        // Step 7: Create a partial payment
        $partialPaymentData = [
            'payable_type' => 'App\Models\Invoice',
            'payable_id' => $invoice->invoice_id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 800.00,
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'PAY-001',
            'notes' => 'Partial payment for premium package',
        ];

        $response = $this->post(route('invoices.payments.store', $invoice), $partialPaymentData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payment = Payment::where('reference_number', 'PAY-001')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(800.00, $payment->amount);

        // Verify invoice status is 'Partially Paid' (no 'Sent') después de un pago parcial
        $invoice->refresh();
        $this->assertEquals('Partially Paid', $invoice->status);
        $this->assertEquals(800.00, $invoice->amount_paid);

        // Step 8: Create final payment to complete the invoice
        $invoice->refresh();
        fwrite(STDERR, "Amount due antes del segundo pago: " . $invoice->amount_due . "\n");
        fwrite(STDERR, "Total amount: " . $invoice->total_amount . "\n");
        fwrite(STDERR, "Amount paid: " . $invoice->amount_paid . "\n");
        
        $finalPaymentData = [
            'payable_type' => 'App\Models\Invoice',
            'payable_id' => $invoice->invoice_id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 849.86, // Remaining amount
            'payment_method' => 'Credit Card',
            'reference_number' => 'PAY-002',
            'notes' => 'Final payment to complete invoice',
        ];

        $response = $this->post(route('invoices.payments.store', $invoice), $finalPaymentData);
        
        // Debug: mostrar el status de la respuesta y errores si existen
        if ($response->status() !== 302) {
            $errors = $response->json('errors') ?? $response->json();
            fwrite(STDERR, 'Errores en segundo pago: ' . print_r($errors, true));
            fwrite(STDERR, 'Status de respuesta: ' . $response->status() . "\n");
        }
        
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify invoice is now paid
        $invoice->refresh();
        $this->assertEquals('Paid', $invoice->status);
        $this->assertTrue(bccomp($expectedTotal, $invoice->amount_paid, 2) == 0, 
            "Expected amount paid {$expectedTotal} but got {$invoice->amount_paid}");

        // Step 9: Verify the complete flow in the database
        $this->assertDatabaseHas('leads', [
            'contact_email' => 'john.doe@example.com',
            'status' => 'Qualified',
        ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'john.doe@example.com',
            'status' => 'Active',
        ]);

        // Usar $invoiceNumber en las siguientes aserciones y búsquedas
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => $invoiceNumber,
            'status' => 'Paid',
            'customer_id' => $customer->customer_id,
        ]);

        $this->assertDatabaseHas('payments', [
            'reference_number' => 'PAY-001',
            'amount' => 800.00,
        ]);

        $this->assertDatabaseHas('payments', [
            'reference_number' => 'PAY-002',
            'amount' => 849.86,
        ]);

        // Step 10: Verify the customer's payment history
        $response = $this->get(route('customers.show', $customer));
        $response->assertOk();
        $response->assertSeeText($invoiceNumber);
        $response->assertSeeText('$800.00');
        $response->assertSeeText('$849.86');
    }

    #[Test]
    public function lead_conversion_with_opportunity_creation()
    {
        // Create a lead
        $lead = Lead::factory()->create([
            'title' => 'Jane Smith - Enterprise Project',
            'contact_name' => 'Jane Smith',
            'contact_email' => 'jane.smith@example.com',
            'status' => 'Qualified',
        ]);

        // Create a customer from the lead
        $customer = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'status' => 'Active',
        ]);

        // Create an opportunity for the customer
        $opportunityData = [
            'customer_id' => $customer->customer_id,
            'name' => 'Enterprise Software Implementation',
            'description' => 'Large enterprise software implementation project',
            'amount' => 50000.00,
            'stage' => 'Proposal',
            'expected_close_date' => now()->addDays(60)->format('Y-m-d'),
            'probability' => 75,
            'assigned_to_user_id' => $this->user->user_id,
        ];

        $response = $this->post(route('opportunities.store'), $opportunityData);
        if ($response->status() === 302) {
            $response->assertRedirect();
            $response->assertSessionHas('success');
        } else {
            // Mostrar errores de validación si existen
            $errors = $response->json('errors') ?? $response->json();
            fwrite(STDERR, 'Errores de validación: ' . print_r($errors, true));
        }

        $opportunity = \App\Models\Opportunity::where('name', 'Enterprise Software Implementation')->first();
        $this->assertNotNull($opportunity);
        fwrite(STDERR, "Opportunity amount: " . $opportunity->amount . "\n");
        $this->assertEquals('Proposal', $opportunity->stage);
        $this->assertEquals(50000.00, $opportunity->amount);

        // Verify the complete relationship chain
        $this->assertDatabaseHas('leads', [
            'contact_email' => 'jane.smith@example.com',
        ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'jane.smith@example.com',
        ]);

        $this->assertDatabaseHas('opportunities', [
            'customer_id' => $customer->customer_id,
            'name' => 'Enterprise Software Implementation',
        ]);
    }
} 