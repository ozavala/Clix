<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use Tests\TestCase;
use Tests\Concerns\HandlesTenantAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerTest extends TestCase
{
    use RefreshDatabase, HandlesTenantAuthentication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    #[Test]
    public function it_can_create_a_customer()
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer@example.com',
            'legal_id' => '11-1111111-001',
            'tenant_id' => $this->tenant->id,
            'created_by_user_id' => $this->adminUser->user_id,
        ]);

        $this->assertDatabaseHas('customers', [
            'customer_id' => $customer->customer_id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer@example.com',
            'tenant_id' => $this->tenant->id,
            'created_by_user_id' => $this->adminUser->user_id,
        ]);
    }

    
    #[Test]
    public function it_automatically_sets_tenant_id_from_authenticated_user()
    {
// The tenant_id should be automatically set by the model's boot method
        $customer = new Customer([
            'first_name' => 'Auto',
            'last_name' => 'Tenant Customer',
            'email' => 'auto-tenant@example.com',
            'legal_id' => '22-2222222-002',
            'created_by_user_id' => $this->adminUser->user_id,
            // Don't set tenant_id here to test the automatic setting
        ]);
        
        $customer->save();

        $this->assertEquals($this->tenant->id, $customer->tenant_id);
    }

   
    #[Test]
    public function it_can_retrieve_customers_for_current_tenant()
    {
        // Create customers for current tenant
        $customers = [];
        for ($i = 0; $i < 3; $i++) {
            $customers[] = Customer::create([
                'first_name' => 'Test',
                'last_name' => 'Customer ' . ($i + 1),
                'email' => 'customer' . ($i + 1) . '@example.com',
                'legal_id' => '33-3333333-00' . ($i + 1),
                'tenant_id' => $this->tenant->id,
                'created_by_user_id' => $this->adminUser->user_id,
            ]);
        }

        // Create a different tenant without using the factory
        $otherTenant = Tenant::create([
            'name' => 'Other Test Tenant',
            'legal_id' => '22-2222222-002',
            'is_active' => true,
            'address' => '456 Other St, Other City',
            'phone' => '987-654-3210',
            'email' => 'other@example.com',
            'industry' => 'Other Industry',
        ]);

        // Create customers for the other tenant
        $otherCustomers = [];
        for ($i = 0; $i < 2; $i++) {
            $otherCustomers[] = Customer::create([
                'first_name' => 'Other',
                'last_name' => 'Customer ' . ($i + 1),
                'email' => 'other' . ($i + 1) . '@example.com',
                'legal_id' => '44-4444444-00' . ($i + 1),
                'tenant_id' => $otherTenant->id,
                'created_by_user_id' => $this->adminUser->user_id,
            ]);
        }

        // Should only retrieve customers for current tenant
        $currentTenantCustomers = Customer::all();
        $this->assertCount(3, $currentTenantCustomers);
        
        // Verify all returned customers belong to the current tenant
        foreach ($currentTenantCustomers as $customer) {
            $this->assertEquals($this->tenant->id, $customer->tenant_id);
            $this->assertTrue($customer->is($customers[0]) || $customer->is($customers[1]) || $customer->is($customers[2]));
        }
    }
}
