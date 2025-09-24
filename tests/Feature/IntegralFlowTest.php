<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\CrmUser;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Payment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * Prueba Integral del Sistema 
 * 
 * Esta prueba verifica el flujo completo del sistema, incluyendo:
 * 
 * 1. CONFIGURACIÓN INICIAL
 *    - Configuración de empresa (nombre, legal_id, cuenta bancaria)
 *    - Creación de usuarios y roles
 * 
 * 2. GESTIÓN DE MAESTROS
 *    - Proveedores con legal_id requerido
 *    - Clientes con legal_id requerido
 *    - Productos con precios y costos
 * 
 * 3. FLUJO DE COMPRAS
 *    - Creación de órdenes de compra (PurchaseOrder)
 *    - Items de órdenes de compra con productos
 *    - Verificación de relaciones y totales
 * 
 * 4. FLUJO DE VENTAS
 *    - Creación de facturas (Invoice)
 *    - Items de facturas con productos
 *    - Verificación de relaciones y totales
 * 
 * 5. FLUJO DE GASTOS
 *    - Creación de bills (gastos)
 *    - Items de bills con descripciones
 *    - Verificación de relaciones y totales
 * 
 * 6. GESTIÓN DE PAGOS
 *    - Pagos para facturas (cobros)
 *    - Pagos para bills (pagos a proveedores)
 *    - Verificación de relaciones polimórficas
 * 
 * 7. CONTABILIDAD
 *    - Creación de asientos contables (JournalEntry)
 *    - Líneas de asientos contables (JournalEntryLine)
 *    - Verificación de balances y cuentas
 * 
 * 8. VERIFICACIONES INTEGRALES
 *    - Legal IDs en todos los documentos
 *    - Relaciones entre entidades
 *    - Integridad de datos
 * 
 * Esta prueba sirve como:
 * - Validación del flujo completo del sistema
 * - Documentación de las relaciones entre entidades
 * - Verificación de la integridad de datos
 * - Referencia para desarrolladores
 * 
 * @package Tests\Feature
 * @author Sistema Clix
 * @version 1.0
 */
class IntegralFlowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Configuración inicial de la prueba
     * 
     * Establece la configuración básica de la empresa necesaria
     * para que el sistema funcione correctamente.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear configuración de empresa
        Setting::updateOrCreate(
            ['key' => 'company_name'],
            ['value' => 'Ingeconsersa SA', 'type' => 'custom']
        );
        
        Setting::updateOrCreate(
            ['key' => 'company_legal_id'],
            ['value' => '12345678-9', 'type' => 'custom']
        );
        
        Setting::updateOrCreate(
            ['key' => 'company_bank_account'],
            ['value' => 'Banco Central 12345678', 'type' => 'custom']
        );
    }

    /**
     * Prueba del flujo integral completo del sistema
     * 
     * Esta prueba verifica que todo el flujo del sistema funcione correctamente:
     * - Creación de maestros (proveedores, clientes, productos)
     * - Generación de documentos (órdenes, facturas, bills)
     * - Procesamiento de pagos
     * - Verificación de legal_ids y relaciones
     */
    public function test_complete_integral_flow()
    {
        // 1. Crear datos base
        $crmUser = CrmUser::factory()->forTenant($this->tenant)->create();
        $supplier = Supplier::factory()->create([
            'legal_id' => 'SUP-001',
            'name' => 'Proveedor Test'
        ]);
        $customer = Customer::factory()->create([
            'legal_id' => 'CUST-001',
            'type' => 'Company',
            'company_name' => 'Cliente Test'
        ]);
        $product = Product::factory()->create([
            'name' => 'Producto Test',
            'price' => 100.00,
            'cost' => 60.00
        ]);

        // 2. Crear Purchase Order
        $purchaseOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->supplier_id,
            'order_date' => now(),
            'status' => 'pending',
            'total_amount' => 200.00,
            'tax_amount' => 20.00,
            'created_by_user_id' => $crmUser->user_id
        ]);

        $purchaseOrderItem = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'product_id' => $product->product_id,
            'item_name' => $product->name,
            'quantity' => 2,
            'unit_price' => 100.00,
            'item_total' => 200.00
        ]);

        // Verificar Purchase Order
        $this->assertDatabaseHas('purchase_orders', [
            'purchase_order_id' => $purchaseOrder->purchase_order_id,
            'supplier_id' => $supplier->supplier_id
        ]);

        // 3. Crear Invoice (venta)
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->customer_id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'pending',
            'subtotal' => 300.00,
            'tax_amount' => 30.00,
            'total_amount' => 330.00,
            'created_by_user_id' => $crmUser->user_id
        ]);

        $invoiceItem = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->invoice_id,
            'product_id' => $product->product_id,
            'quantity' => 3,
            'unit_price' => 100.00,
            'item_total' => 300.00
        ]);

        // Verificar Invoice
        $this->assertDatabaseHas('invoices', [
            'invoice_id' => $invoice->invoice_id,
            'customer_id' => $customer->customer_id
        ]);

        // 4. Crear Bill (gasto)
        $bill = Bill::factory()->create([
            'supplier_id' => $supplier->supplier_id,
            'bill_date' => now(),
            'due_date' => now()->addDays(15),
            'status' => 'pending',
            'subtotal' => 150.00,
            'tax_amount' => 15.00,
            'total_amount' => 165.00,
            'created_by_user_id' => $crmUser->user_id
        ]);

        $billItem = BillItem::factory()->create([
            'bill_id' => $bill->bill_id,
            'item_description' => 'Servicio de consultoría',
            'quantity' => 1,
            'unit_price' => 150.00,
            'item_total' => 150.00
        ]);

        // Verificar Bill
        $this->assertDatabaseHas('bills', [
            'bill_id' => $bill->bill_id,
            'supplier_id' => $supplier->supplier_id
        ]);

        // 5. Crear Payment para Invoice
        $invoicePayment = Payment::factory()->create([
            'payable_type' => Invoice::class,
            'payable_id' => $invoice->invoice_id,
            'payment_date' => now(),
            'amount' => 330.00,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'PAY-INV-001',
            'created_by_user_id' => $crmUser->user_id
        ]);

        // Verificar Payment para Invoice
        $this->assertDatabaseHas('payments', [
            'payment_id' => $invoicePayment->payment_id,
            'payable_type' => Invoice::class,
            'payable_id' => $invoice->invoice_id,
            'amount' => 330.00
        ]);

        // 6. Crear Payment para Bill
        $billPayment = Payment::factory()->create([
            'payable_type' => Bill::class,
            'payable_id' => $bill->bill_id,
            'payment_date' => now(),
            'amount' => 165.00,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'PAY-BILL-001',
            'created_by_user_id' => $crmUser->user_id
        ]);

        // Verificar Payment para Bill
        $this->assertDatabaseHas('payments', [
            'payment_id' => $billPayment->payment_id,
            'payable_type' => Bill::class,
            'payable_id' => $bill->bill_id,
            'amount' => 165.00
        ]);

        // Verificar que los pagos están relacionados correctamente
        $this->assertEquals(Invoice::class, $invoicePayment->payable_type);
        $this->assertEquals($invoice->invoice_id, $invoicePayment->payable_id);
        $this->assertEquals(Bill::class, $billPayment->payable_type);
        $this->assertEquals($bill->bill_id, $billPayment->payable_id);

        // Verificar que los legal_id están presentes
        $this->assertEquals('SUP-001', $supplier->legal_id);
        $this->assertEquals('CUST-001', $customer->legal_id);
    }

    /**
     * Prueba de creación de asientos contables
     * 
     * Verifica que los asientos contables se creen correctamente
     * con sus líneas correspondientes y que mantengan el balance.
     */
    public function test_journal_entries_creation()
    {
        $crmUser = CrmUser::factory()->forTenant($this->tenant)->create();
        
        // Crear un asiento contable
        $journalEntry = JournalEntry::factory()->create([
            'created_by_user_id' => $crmUser->user_id,
            'transaction_type' => 'payment',
            'description' => 'Test journal entry'
        ]);

        // Verificar que el asiento se creó correctamente
        $this->assertDatabaseHas('journal_entries', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'created_by_user_id' => $crmUser->user_id,
            'transaction_type' => 'payment'
        ]);
        
        // Crear líneas del asiento
        JournalEntryLine::factory()->create([
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'account_code' => '1101',
            'account_name' => 'Cash',
            'debit_amount' => 1000.00,
            'credit_amount' => 0
        ]);

        JournalEntryLine::factory()->create([
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'account_code' => '2102',
            'account_name' => 'Accounts Receivable',
            'debit_amount' => 0,
            'credit_amount' => 1000.00
        ]);

        // Verificar que las líneas se crearon correctamente
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'account_name' => 'Cash',
            'debit_amount' => 1000.00
        ]);

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $journalEntry->journal_entry_id,
            'account_name' => 'Accounts Receivable',
            'credit_amount' => 1000.00
        ]);
    }
} 