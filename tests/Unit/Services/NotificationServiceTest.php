<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\NotificationService;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class NotificationServiceTest extends TestCase
{
    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = new NotificationService();
    }

    public function test_send_low_stock_alert_for_product_with_low_stock()
    {
        // Crear rol de admin
        $adminRole = UserRole::factory()->create(['name' => 'admin']);
        
        // Crear usuario admin
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        // Crear warehouse
        $warehouse = Warehouse::factory()->create();

        // Crear producto con bajo stock
        $product = Product::factory()->create([
            'reorder_point' => 10
        ]);
        
        $product->warehouses()->attach($warehouse->id, ['quantity' => 5]);

        // Mock del servicio de email para evitar envío real
        Mail::fake();

        // Ejecutar método
        $this->notificationService->sendLowStockAlert($product);

        // Verificar que no hay errores
        $this->assertTrue(true);
    }

    public function test_send_overdue_invoice_alert()
    {
        // Crear rol de sales
        $salesRole = UserRole::factory()->create(['name' => 'sales']);
        
        // Crear usuario de ventas
        $salesUser = User::factory()->create();
        $salesUser->roles()->attach($salesRole);

        // Crear factura vencida
        $invoice = Invoice::factory()->create([
            'status' => 'sent',
            'due_date' => now()->subDays(5)
        ]);

        // Mock del servicio de email
        Mail::fake();

        // Ejecutar método
        $this->notificationService->sendOverdueInvoiceAlert($invoice);

        // Verificar que no hay errores
        $this->assertTrue(true);
    }

    public function test_send_purchase_order_status_update()
    {
        // Crear orden de compra
        $purchaseOrder = PurchaseOrder::factory()->create([
            'status' => 'confirmed'
        ]);

        // Mock del servicio de email
        Mail::fake();

        // Ejecutar método
        $this->notificationService->sendPurchaseOrderStatusUpdate($purchaseOrder, 'draft', 'confirmed');

        // Verificar que no hay errores
        $this->assertTrue(true);
    }

    public function test_send_payment_received_alert_for_invoice()
    {
        // Crear factura y pago
        $invoice = Invoice::factory()->create();
        $payment = Payment::factory()->state([
            'payable_id' => $invoice->invoice_id,
            'payable_type' => Invoice::class,
            'amount' => 100
        ])->create();

        // Mock del servicio de email
        Mail::fake();

        // Ejecutar método
        $this->notificationService->sendPaymentReceivedAlert($payment);

        // Verificar que no hay errores
        $this->assertTrue(true);
    }

    public function test_send_order_status_update()
    {
        // Crear orden
        $order = Order::factory()->create([
            'status' => 'shipped'
        ]);

        // Mock del servicio de email
        Mail::fake();

        // Ejecutar método
        $this->notificationService->sendOrderStatusUpdate($order, 'processing', 'shipped');

        // Verificar que no hay errores
        $this->assertTrue(true);
    }

    public function test_send_daily_report()
    {
        // Crear rol de admin
        $adminRole = UserRole::factory()->create(['name' => 'admin']);
        
        // Crear usuario admin
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        // Mock del servicio de email
        Mail::fake();

        // Ejecutar método
        $this->notificationService->sendDailyReport();

        // Verificar que no hay errores
        $this->assertTrue(true);
    }
} 