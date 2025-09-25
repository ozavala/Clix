<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\CrmUser;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;


class BillControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;
    protected $logFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up a dedicated log file
        $this->logFile = storage_path('logs/test_debug.log');
        file_put_contents($this->logFile, "=== Starting Test ===\n", FILE_APPEND);
        
        $this->log("Setting up test...");
        
        try {
            $this->tenant = \App\Models\Tenant::first() ?? \App\Models\Tenant::factory()->create();
            $this->log("Tenant ID: " . $this->tenant->id);
            
            $this->user = CrmUser::factory()->create(['tenant_id' => $this->tenant->id]);
            $this->log("User ID: " . $this->user->user_id);
            
            $this->actingAs($this->user , 'crm');
            $this->log("Test setup complete");
        } catch (\Exception $e) {
            $this->log("Setup failed: " . $e->getMessage());
            throw $e;
        }
    }

    protected function log($message)
    {
        $timestamp = now()->toDateTimeString();
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    #[Test]
public function it_can_store_a_bill_via_controller()
{
    // Arrange
    $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);
    $purchaseOrder = PurchaseOrder::factory()->create([
        'tenant_id' => $this->tenant->id,
        'supplier_id' => $supplier->supplier_id,
        'created_by_user_id' => $this->user->user_id,
        'status' => 'approved'
    ]);

    $billData = [
    'tenant_id' => $this->tenant->id,
    'supplier_id' => $supplier->supplier_id,
    'bill_number' => 'CTRL-BILL-001',
    'bill_date' => now()->format('Y-m-d'),
    'due_date' => now()->addDays(30)->format('Y-m-d'),
    'tax_amount' => 180.00,
    'status' => 'draft',
    'notes' => 'Created via controller test',
    // ✅ Add items
    'items' => [
        [
            'item_name' => 'Product 1',
            'quantity' => 2,
            'unit_price' => 500.00,
        ]
    ],
];

    // Act
    $response = $this->post(route('bills.store'), $billData);

    // 🔍 Depuración crítica:
    //$response->dump(); // Esto mostrará errores si hay redirección

    // Assert
    $response->assertStatus(302); // o 201
    $this->assertDatabaseHas('bills', [
        'bill_number' => 'CTRL-BILL-001',
        'tenant_id' => $this->tenant->id,
    ]);
}
    
    #[Test]
    public function test_authenticated_user_tenant_id()
    {
        $this->assertEquals($this->tenant->id, auth()->user()->tenant_id);
        //dump('Auth tenant ID:', auth()->user()->tenant_id); // Debug line
    }

    /*#[Test]
    public function test_basic_models_can_be_created()
    {
        try {
            $this->log("Starting test_basic_models_can_be_created");
            
            // Test 1: Can create a supplier
            $this->log("Creating supplier...");
            $supplier = Supplier::factory()->create(['tenant_id' => $this->tenant->id]);
            $this->log("Supplier created with ID: " . $supplier->supplier_id);
            
            // Test 2: Can create a product
            $this->log("Creating product...");
            $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
            $this->log("Product created with ID: " . $product->product_id);
            
            // Test 3: Can create a purchase order
            $this->log("Creating purchase order...");
            $purchaseOrder = PurchaseOrder::factory()->create([
                'tenant_id' => $this->tenant->id,
                'supplier_id' => $supplier->supplier_id,
                'created_by_user_id' => $this->user->user_id,
                'status' => 'approved'
            ]);
            $this->log("Purchase order created with ID: " . $purchaseOrder->purchase_order_id);

            // Test 4: Skip bill creation for now
            $this->log("Skipping bill creation in this test");
            $this->assertTrue(true);
            
        } catch (\Exception $e) {
            $this->log("Test failed: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    // tests/Feature/BillControllerTest.php*/
    public function test_tenant_creation()
    {
        Log::info('=== Starting tenant creation test ===');
        $tenant = Tenant::first() ?? Tenant::factory()->create();
        $this->assertNotNull($tenant->id);
        Log::info('Tenant created', ['id' => $tenant->id]);
        return $tenant;
    }

    public function test_user_creation()
    {
        Log::info('=== Starting user creation test ===');
        $tenant = Tenant::first() ?? Tenant::factory()->create();
        $user = CrmUser::factory()->create(['tenant_id' => $tenant->id]);
        $this->assertNotNull($user->user_id);
        Log::info('User created', ['id' => $user->user_id]);
        return $user;
    }

    public function test_supplier_creation()
    {
        Log::info('=== Starting supplier creation test ===');
        $tenant = Tenant::first() ?? Tenant::factory()->create();
        $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
        $this->assertNotNull($supplier->supplier_id);
        Log::info('Supplier created', ['id' => $supplier->supplier_id]);
        return $supplier;
    }

    public function test_purchase_order_creation()
    {
        Log::info('=== Starting purchase order creation test ===');
        $tenant = Tenant::first() ?? Tenant::factory()->create();
        $user = CrmUser::factory()->create(['tenant_id' => $tenant->id]);
        $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
        
        $po = PurchaseOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'supplier_id' => $supplier->supplier_id,
            'created_by_user_id' => $user->user_id,
            'status' => 'approved'
        ]);
        
        $this->assertNotNull($po->purchase_order_id);
        Log::info('Purchase order created', ['id' => $po->purchase_order_id]);
        return $po;
    }

    public function test_bill_creation()
    {
        Log::info('=== Starting bill creation test ===');
        
        try {
            // Create required models
            $tenant = Tenant::first() ?? Tenant::factory()->create();
            $user = CrmUser::factory()->create(['tenant_id' => $tenant->id]);
            $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
            
            $purchaseOrder = PurchaseOrder::factory()->create([
                'tenant_id' => $tenant->id,
                'supplier_id' => $supplier->supplier_id,
                'created_by_user_id' => $user->user_id,
                'status' => 'approved'
            ]);

            // Test creating a bill directly
            Log::info('Creating bill...');
            $bill = \App\Models\Bill::create([
                'tenant_id' => $tenant->id,
                'purchase_order_id' => $purchaseOrder->purchase_order_id,
                'supplier_id' => $supplier->supplier_id,
                'created_by_user_id' => $user->user_id,
                'bill_number' => 'TEST-BILL-001',
                'bill_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'status' => 'draft',
                'subtotal' => 100.00,
                'tax_amount' => 18.00,
                'total_amount' => 118.00,
                'notes' => 'Test bill creation'
            ]);

            $this->assertNotNull($bill->bill_id);
            Log::info('Bill created successfully', ['id' => $bill->bill_id]);
            
        } catch (\Exception $e) {
            Log::error('Bill creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
}