<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\CrmUser;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Log;

class DebugBillTest extends TestCase
{
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