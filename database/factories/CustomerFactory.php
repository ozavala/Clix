<?php

namespace Database\Factories;

use App\Models\CrmUser;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['Person', 'Company']);

        $data = [
            'tenant_id' => $this->tenant_id ?? Tenant::factory(),
            'created_by_user_id' => $this->created_by_user_id ?? CrmUser::inRandomOrder()->first()->user_id ?? CrmUser::factory(),
            'type' => $type,
            'legal_id' => $this->faker->unique()->bothify('??-#######-#'), // e.g., AB-1234567-8
            'email' => $this->faker->unique()->safeEmail,
            'phone_number' => $this->faker->phoneNumber,
            'status' => $this->faker->randomElement(['Active', 'Inactive', 'Lead', 'Prospect']),
            'first_name' => $type === 'Person' ? $this->faker->firstName : null,
            'last_name' => $type === 'Person' ? $this->faker->lastName : null,
            'company_name' => $type === 'Company' ? $this->faker->company : null,
            'address_street' => $this->faker->streetAddress,
            'address_city' => $this->faker->city,
            'address_state' => $this->faker->state,
            'address_postal_code' => $this->faker->postcode,
            'address_country' => $this->faker->country,
            // 'notes' => $this->faker->paragraph, // Removed to avoid conflict with
        ];
        
        // Only set these if not already set (allows tests to override)
        if (!isset($this->tenant_id)) {
            $data['tenant_id'] = Tenant::factory();
        }
        
        if (!isset($this->created_by_user_id)) {
            $data['created_by_user_id'] = CrmUser::inRandomOrder()->first()->user_id ?? CrmUser::factory();
        }
        
        // Configure the factory to create a contact when a customer is created
        $this->afterCreating(function (Customer $customer) {
            $customer->contacts()->create([
                'first_name' => $customer->first_name ?: $this->faker->firstName,
                'last_name' => $customer->last_name ?: $this->faker->lastName,
                'email' => $customer->email,
                'phone' => $customer->phone_number,
                'title' => $this->faker->jobTitle,
                'created_by_user_id' => $customer->created_by_user_id,
                'tenant_id' => $customer->tenant_id,
                'contactable_type' => Customer::class,
                'contactable_id' => $customer->customer_id
            ]);
        });

        return $data;
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant),
        ]);
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Customer $customer) {
            $customer->contacts()->create([
                'tenant_id' => $customer->tenant_id,
                'contactable_id' => $customer->customer_id,
                'contactable_type' => 'customer',
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'email' => $this->faker->unique()->safeEmail(),
                'phone' => $this->faker->phoneNumber(),
                'title' => $this->faker->jobTitle(),
                
                'created_by_user_id' => $customer->created_by_user_id,
            ]);
        });
    }
}