<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InvoiceItem;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    public function test_invoice_belongs_to_customer()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->create();
        $invoice = Invoice::factory()->forTenant($this->tenant, $customer)->create();

        $this->assertInstanceOf(Customer::class, $invoice->customer);
        $this->assertTrue($invoice->customer->is($customer));
    }

    public function test_invoice_has_many_items()
    {
        $invoice = Invoice::factory()->forTenant($this->tenant)->has(InvoiceItem::factory()->count(2)->forTenant($this->tenant), 'items')->create();

        $this->assertCount(2, $invoice->items);
        $this->assertInstanceOf(InvoiceItem::class, $invoice->items->first());
    }
} 