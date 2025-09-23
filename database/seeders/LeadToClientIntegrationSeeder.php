<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lead;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\CrmUser;
use Carbon\Carbon;

class LeadToClientIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create a tenant
        $tenant = Tenant::first() ?? Tenant::factory()->create();
        
        // Create a user for the tenant
        $user = CrmUser::factory()->forTenant($tenant)->create();

        // 2. Crear un lead
        $lead = Lead::factory()->create([
            'tenant_id' => 1,
            'title' => 'Empresa Ejemplo - Proyecto ERP',
            'contact_name' => 'Juan Pérez',
            'contact_email' => 'juan.perez@ejemplo.com',
            'status' => 'Qualified',
        ]);

        // 3. Convertir el lead en cliente
        $customer = Customer::factory()->create([
            'tenant_id' => 1,
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'company_name' => 'Empresa Ejemplo',
            'email' => 'juan.perez@ejemplo.com',
            'status' => 'Active',
        ]);

        // 4. Crear una oportunidad para el cliente
        $opportunity = Opportunity::create([
            'tenant_id' => 1,
            'name' => 'Implementación ERP',
            'description' => 'Proyecto de implementación de ERP',
            'customer_id' => $customer->customer_id,
            'stage' => 'Proposal',
            'amount' => 25000.00,
            'expected_close_date' => Carbon::now()->addDays(30),
            'probability' => 70,
            'assigned_to_user_id' => $user->user_id,
            'created_by_user_id' => $user->user_id,
        ]);

        // 5. Crear productos
        $product1 = Product::factory()->create([
            'tenant_id' => 1,
            'name' => 'Licencia ERP',
            'sku' => 'ERP-001',
            'price' => 15000.00,
        ]);
        $product2 = Product::factory()->create([
            'tenant_id' => 1,
            'name' => 'Soporte Anual',
            'sku' => 'SUP-001',
            'price' => 10000.00,
        ]);

        // 6. Crear una factura para el cliente
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'customer_id' => $customer->customer_id,
            'invoice_number' => 'INV-INT-001',
            'invoice_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'status' => 'Sent',
            'subtotal' => 25000.00,
            'tax_amount' => 2500.00,
            'total_amount' => 27500.00,
            'amount_paid' => 0.00,
            'created_by_user_id' => $user->user_id,
        ]);

        // 7. Asociar productos a la factura
        InvoiceItem::create([
            
            'invoice_id' => $invoice->invoice_id,
            'product_id' => $product1->product_id,
            'item_name' => $product1->name,
            'item_description' => $product1->description,
            'quantity' => 1,
            'unit_price' => $product1->price,
            'item_total' => $product1->price,
        ]);
        InvoiceItem::create([
            
            'invoice_id' => $invoice->invoice_id,
            'product_id' => $product2->product_id,
            'item_name' => $product2->name,
            'item_description' => $product2->description,
            'quantity' => 1,
            'unit_price' => $product2->price,
            'item_total' => $product2->price,
        ]);

        // 8. Crear pagos parciales para la factura
        Payment::create([
            'tenant_id' => 1,
            'payable_type' => Invoice::class,
            'payable_id' => $invoice->invoice_id,
            'payment_date' => Carbon::now(),
            'amount' => 15000.00,
            'payment_method' => 'Transferencia',
            'reference_number' => 'PAY-INT-001',
            'created_by_user_id' => $user->user_id,
        ]);
        Payment::create([
            'tenant_id' => 1,
            'payable_type' => Invoice::class,
            'payable_id' => $invoice->invoice_id,
            'payment_date' => Carbon::now()->addDays(10),
            'amount' => 12500.00,
            'payment_method' => 'Tarjeta',
            'reference_number' => 'PAY-INT-002',
            'created_by_user_id' => $user->user_id,
        ]);
    }
} 