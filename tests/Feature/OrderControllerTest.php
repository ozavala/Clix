<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\CrmUser;
use App\Models\Permission;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class OrderControllerTest extends TestCase
{
    protected CrmUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SettingsTableSeeder::class);
        $this->user = CrmUser::factory()->forTenant($this->tenant)->create();
        $this->actingAs($this->user, 'crm');
        // Asignar permisos necesarios para gestión de órdenes
        // Esto es requerido por la lógica de autorización en el controlador de órdenes
        $this->givePermission($this->user, [
            'view-orders',
            'create-orders',
            'edit-orders',
            'delete-orders'
        ]);
    }

    #[Test]
    public function it_can_display_orders_index()
    {
        Order::factory()->forTenant($this->tenant)->count(3)->create();

        $response = $this->get(route('orders.index'));

        $response->assertOk();
        $response->assertViewIs('orders.index');
        $response->assertViewHas('orders');
    }

    #[Test]
    public function it_can_search_orders()
    {
        Order::factory()->forTenant($this->tenant)->create(['order_number' => 'ORD-001']);
        Order::factory()->forTenant($this->tenant)->create(['order_number' => 'ORD-002']);

        $response = $this->get(route('orders.index', ['search' => 'ORD-001']));

        $response->assertOk();
        $response->assertViewHas('orders');
        $response->assertSee('ORD-001');
        $response->assertDontSee('ORD-002');
    }

    #[Test]
    public function it_can_display_create_order_form()
    {
        $response = $this->get(route('orders.create'));

        $response->assertOk();
        $response->assertViewIs('orders.create');
        $response->assertViewHas('customers');
        $response->assertViewHas('products');
    }

    #[Test]
    public function it_can_store_a_new_order()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->create();
        $product = Product::factory()->forTenant($this->tenant)->create();
        
        $orderData = [
            'customer_id' => $customer->customer_id,
            'order_date' => '2024-01-15',
            'status' => 'Pending',
            'notes' => 'Test order notes',
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'item_name' => $product->name,
                    'quantity' => 2,
                    'unit_price' => 50.00
                ]
            ]
        ];

        $response = $this->post(route('orders.store'), $orderData);

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('success', 'Order created successfully.');

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->customer_id,
            'status' => 'Pending',
            'notes' => 'Test order notes',
        ]);

        $order = Order::where('customer_id', $customer->customer_id)->first();
        $this->assertNotNull($order);
        $this->assertCount(1, $order->items);
        $this->assertEquals(2, $order->items->first()->quantity);
        $this->assertEquals(50.00, $order->items->first()->unit_price);
    }

    #[Test]
    public function it_can_display_order_details()
    {
        $order = Order::factory()->forTenant($this->tenant)->create();

        $response = $this->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertViewIs('orders.show');
        $response->assertViewHas('order');
        $response->assertSee($order->order_number);
    }

    #[Test]
    public function it_can_display_edit_order_form()
    {
        $order = Order::factory()->forTenant($this->tenant)->create();

        $response = $this->get(route('orders.edit', $order));

        $response->assertOk();
        $response->assertViewIs('orders.edit');
        $response->assertViewHas('order');
        $response->assertViewHas('customers');
        $response->assertViewHas('products');
    }

    #[Test]
    public function it_can_update_order()
    {
        $order = Order::factory()->forTenant($this->tenant)->create();
        $customer = Customer::factory()->forTenant($this->tenant)->create();
        $product = Product::factory()->forTenant($this->tenant)->create();
        
        $updateData = [
            'customer_id' => $customer->customer_id,
            'order_date' => '2024-02-15',
            'status' => 'Processing',
            'notes' => 'Updated order notes',
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'item_name' => $product->name,
                    'quantity' => 3,
                    'unit_price' => 75.00
                ]
            ]
        ];

        $response = $this->put(route('orders.update', $order), $updateData);

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('success', 'Order updated successfully.');

        $this->assertDatabaseHas('orders', [
            'order_id' => $order->order_id,
            'customer_id' => $customer->customer_id,
            'status' => 'Processing',
            'notes' => 'Updated order notes',
        ]);

        $order->refresh();
        $this->assertCount(1, $order->items);
        $this->assertEquals(3, $order->items->first()->quantity);
        $this->assertEquals(75.00, $order->items->first()->unit_price);
    }

    #[Test]
    public function it_can_delete_order()
    {
        $order = Order::factory()->forTenant($this->tenant)->create();

        $response = $this->delete(route('orders.destroy', $order));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('success', 'Order deleted successfully.');

        $this->assertSoftDeleted('orders', ['order_id' => $order->order_id]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_order()
    {
        $response = $this->post(route('orders.store'), []);

        $response->assertSessionHasErrors(['customer_id', 'order_date', 'status']);
    }

    #[Test]
    public function it_validates_order_date_format()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->create();
        
        $orderData = [
            'customer_id' => $customer->customer_id,
            'order_date' => 'invalid-date',
            'status' => 'Pending',
        ];

        $response = $this->post(route('orders.store'), $orderData);

        $response->assertSessionHasErrors(['order_date']);
    }

    #[Test]
    public function it_validates_order_items_are_required()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->create();
        
        $orderData = [
            'customer_id' => $customer->customer_id,
            'order_date' => '2024-01-15',
            'status' => 'Pending',
            'items' => []
        ];

        $response = $this->post(route('orders.store'), $orderData);

        $response->assertSessionHasErrors(['items']);
    }

    #[Test]
    public function it_can_handle_multiple_order_items()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->create();
        $product1 = Product::factory()->forTenant($this->tenant)->create();
        $product2 = Product::factory()->forTenant($this->tenant)->create();
        
        $orderData = [
            'customer_id' => $customer->customer_id,
            'order_date' => '2024-01-15',
            'status' => 'Pending',
            'notes' => 'Order with multiple items',
            'items' => [
                [
                    'product_id' => $product1->product_id,
                    'item_name' => $product1->name,
                    'quantity' => 2,
                    'unit_price' => 50.00
                ],
                [
                    'product_id' => $product2->product_id,
                    'item_name' => $product2->name,
                    'quantity' => 1,
                    'unit_price' => 100.00
                ]
            ]
        ];

        $response = $this->post(route('orders.store'), $orderData);

        $response->assertRedirect(route('orders.index'));

        $order = Order::where('customer_id', $customer->customer_id)->first();
        $this->assertNotNull($order);
        $this->assertCount(2, $order->items);
        
        // Check that both items are properly stored
        $itemQuantities = $order->items->pluck('quantity')->toArray();
        $this->assertContains(2, $itemQuantities);
        $this->assertContains(1, $itemQuantities);
    }

    #[Test]
    public function it_can_calculate_order_totals()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->create();
        $product = Product::factory()->forTenant($this->tenant)->create();
        
        $orderData = [
            'customer_id' => $customer->customer_id,
            'order_date' => '2024-01-15',
            'status' => 'Pending',
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'item_name' => $product->name,
                    'quantity' => 2,
                    'unit_price' => 50.00
                ]
            ]
        ];

        $response = $this->post(route('orders.store'), $orderData);

        $response->assertRedirect(route('orders.index'));

        $order = Order::where('customer_id', $customer->customer_id)->first();
        $this->assertNotNull($order);
        
        // Calculate expected totals
        $subtotal = 2 * 50.00; // quantity * unit_price
        $total = $subtotal; // No discount in this test
        
        $this->assertEquals($subtotal, $order->subtotal);
        $this->assertEquals($total, $order->total_amount);
    }
} 