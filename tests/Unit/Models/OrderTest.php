<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\Customer;
use App\Models\OrderItem;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    public function test_order_belongs_to_customer()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->create();
        $order = Order::factory()->forTenant($this->tenant)->for($customer)->create();

        $this->assertInstanceOf(Customer::class, $order->customer);
        $this->assertTrue($order->customer->is($customer));
    }

    public function test_order_has_many_items()
    {
        $order = Order::factory()->forTenant($this->tenant)->has(OrderItem::factory()->count(3)->forTenant($this->tenant), 'items')->create();

        $this->assertCount(3, $order->items);
        $this->assertInstanceOf(OrderItem::class, $order->items->first());
    }
} 