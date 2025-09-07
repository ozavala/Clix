<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Invoice;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    public function test_payment_belongs_to_invoice()
    {
        $invoice = Invoice::factory()->forTenant($this->tenant)->create();
        $payment = Payment::factory()->forTenant($this->tenant)->create([
            'payable_type' => Invoice::class,
            'payable_id' => $invoice->invoice_id,
        ]);

        $this->assertInstanceOf(Invoice::class, $payment->payable);
        $this->assertTrue($payment->payable->is($invoice));
    }
} 