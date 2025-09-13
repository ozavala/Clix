<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CrmUser;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    protected int $obLevel;
    protected CrmUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Record initial buffer level to avoid PHPUnit risky warnings
        $this->obLevel = ob_get_level();
        $this->seed(\Database\Seeders\SettingsTableSeeder::class);
        $this->user = CrmUser::factory()->forTenant($this->tenant)->create();
        $this->actingAs($this->user);
        // Asignar permisos necesarios para gestión de clientes
        // Esto es requerido por la lógica de autorización en el controlador de clientes
        $this->givePermission($this->user, [
            'view-customers',
            'create-customers',
            'edit-customers',
            'delete-customers'
        ]);
    }

    protected function tearDown(): void
    {
        // Close any buffers opened during the test run only
        while (ob_get_level() > $this->obLevel) {
            @ob_end_clean();
        }
        parent::tearDown();
    }

    #[Test]
    public function it_can_display_customers_index()
    {
        // Create some test customers
        Customer::factory()->forTenant($this->tenant)->count(3)->create();

        $response = $this->get(route('customers.index'));

        $response->assertOk();
        $response->assertViewIs('customers.index');
        $response->assertViewHas('customers');
    }

    #[Test]
    public function it_can_search_customers()
    {
        // Create customers with specific names
        Customer::factory()->forTenant($this->tenant)->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Customer::factory()->forTenant($this->tenant)->create(['first_name' => 'Jane', 'last_name' => 'Smith']);
        Customer::factory()->forTenant($this->tenant)->create(['first_name' => 'Bob', 'last_name' => 'Johnson']);

        $response = $this->get(route('customers.index', ['search' => 'John']));

        $response->assertOk();
        $response->assertViewHas('customers');
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');
    }

    #[Test]
    public function it_can_display_create_customer_form()
    {
        $response = $this->get(route('customers.create'));

        $response->assertOk();
        $response->assertViewIs('customers.create');
        $response->assertViewHas('statuses');
    }

    #[Test]
    public function it_can_store_a_new_customer()
    {
        $customerData = [
            'type' => 'Person',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'legal_id' => 'PERSON-001',
            'email' => 'john.doe@example.com',
            'phone_number' => '123-456-7890',
            'status' => 'Active',
            'addresses' => [
                [
                    'street_address_line_1' => '123 Main St',
                    'city' => 'Anytown',
                    'state_province' => 'CA',
                    'postal_code' => '12345',
                    'country_code' => 'USA',
                    'is_primary' => true,
                ]
            ]
        ];

        $response = $this->post(route('customers.store'), $customerData);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success', 'Customer created successfully.');

        $this->assertDatabaseHas('customers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'legal_id' => 'PERSON-001',
            'status' => 'Active',
            'created_by_user_id' => $this->user->user_id,
        ]);

        $customer = Customer::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertCount(1, $customer->addresses);
        $this->assertTrue($customer->addresses->first()->is_primary);
    }

    #[Test]
    public function it_can_display_customer_details()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->create();

        $response = $this->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertViewIs('customers.show');
        $response->assertViewHas('customer');
        $response->assertSee($customer->first_name);
        $response->assertSee($customer->last_name);
    }

    #[Test]
    public function it_can_display_edit_customer_form()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->create();

        $response = $this->get(route('customers.edit', $customer));

        $response->assertOk();
        $response->assertViewIs('customers.edit');
        $response->assertViewHas('customer');
        $response->assertViewHas('statuses');
    }

    #[Test]
    public function it_can_update_customer()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);

        $updateData = [
            'type' => 'Person',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'legal_id' => 'PERSON-002',
            'email' => 'jane.smith@example.com',
            'phone_number' => '987-654-3210',
            'status' => 'Inactive',
            'addresses' => [
                [
                    'street_address_line_1' => '456 Oak Ave',
                    'city' => 'Somewhere',
                    'state_province' => 'NY',
                    'postal_code' => '54321',
                    'country_code' => 'USA',
                    'is_primary' => true,
                ]
            ]
        ];

        $response = $this->put(route('customers.update', $customer), $updateData);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success', 'Customer updated successfully.');

        $this->assertDatabaseHas('customers', [
            'customer_id' => $customer->customer_id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'legal_id' => 'PERSON-002',
            'status' => 'Inactive',
        ]);

        $customer->refresh();
        $this->assertCount(1, $customer->addresses);
        $this->assertEquals('456 Oak Ave', $customer->addresses->first()->street_address_line_1);
    }

    #[Test]
    public function it_can_delete_customer()
    {
        $customer = Customer::factory()->forTenant($this->tenant)->create();

        $response = $this->delete(route('customers.destroy', $customer));

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success', 'Customer deleted successfully.');

        $this->assertSoftDeleted('customers', ['customer_id' => $customer->customer_id]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_customer()
    {
        $response = $this->post(route('customers.store'), []);

        $response->assertSessionHasErrors(['type', 'legal_id', 'status']);
    }

    #[Test]
    public function it_validates_email_format()
    {
        $customerData = [
            'type' => 'Person',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'legal_id' => 'PERSON-003',
            'email' => 'invalid-email',
            'status' => 'Active',
        ];

        $response = $this->post(route('customers.store'), $customerData);

        $response->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function it_can_handle_multiple_addresses()
    {
        $customerData = [
            'type' => 'Person',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'legal_id' => 'PERSON-004',
            'email' => 'john.doe@example.com',
            'status' => 'Active',
            'addresses' => [
                [
                    'street_address_line_1' => '123 Main St',
                    'city' => 'Anytown',
                    'state_province' => 'CA',
                    'postal_code' => '12345',
                    'country_code' => 'USA',
                    'is_primary' => true,
                ],
                [
                    'street_address_line_1' => '456 Oak Ave',
                    'city' => 'Somewhere',
                    'state_province' => 'NY',
                    'postal_code' => '54321',
                    'country_code' => 'USA',
                    'is_primary' => false,
                ]
            ]
        ];

        $response = $this->post(route('customers.store'), $customerData);

        $response->assertRedirect(route('customers.index'));

        $customer = Customer::where('email', 'john.doe@example.com')->first();
        $this->assertCount(2, $customer->addresses);
        
        $primaryAddress = $customer->addresses->where('is_primary', true)->first();
        $this->assertNotNull($primaryAddress);
        $this->assertEquals('123 Main St', $primaryAddress->street_address_line_1);
    }
} 