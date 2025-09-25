<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Bill;
use App\Models\Tenant;
use App\Models\Supplier;
use App\Models\CrmUser;

class MinimalBillTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    public function test_minimal_bill_creation()
    {
        // Disable all middleware and event listeners
        $this->withoutMiddleware();
        Bill::flushEventListeners();
        
        // Create required models
        $tenant = Tenant::first() ?? Tenant::factory()->create();
        $user = CrmUser::factory()->create(['tenant_id' => $tenant->id]);
        $supplier = Supplier::factory()->create(['tenant_id' => $tenant->id]);
        
        // Set tenant context
        config(['tenant_id' => $tenant->id]);
        $this->actingAs($user);

        // Test creating a bill with all required fields
        $bill = new Bill([
            'tenant_id' => $tenant->id,
            'supplier_id' => $supplier->supplier_id,
            'created_by_user_id' => $user->user_id,
            'bill_number' => 'TEST-001',
            'bill_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'draft',
            'subtotal' => 100.00,
            'tax_amount' => 16.00,
            'total_amount' => 116.00
        ]);
        
        $saved = $bill->save();
        
        $this->assertTrue($saved, 'Failed to save bill');
        $this->assertNotNull($bill->bill_id);
    }
}