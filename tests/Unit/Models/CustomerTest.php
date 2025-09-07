<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Contact;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    public function test_customer_can_have_multiple_contacts()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->hasContacts(1)->create();

        $this->assertCount(2, $customer->contacts);
        $this->assertInstanceOf(Contact::class, $customer->contacts->first());
    }

    public function test_customer_has_orders_relationship()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->hasOrders(2)->create();

        $this->assertCount(2, $customer->orders);
        $this->assertInstanceOf(\App\Models\Order::class, $customer->orders->first());
    }

    public function test_customer_has_invoices_relationship()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->has(Invoice::factory()->count(4)->forTenant($this->tenant), 'invoices')->create();
        $this->assertCount(4, $customer->invoices);
        $this->assertInstanceOf(Invoice::class, $customer->invoices->first());
    }
} 