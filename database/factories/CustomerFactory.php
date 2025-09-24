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
            'type' => $type,
            'first_name' => $type === 'Person' ? $this->faker->firstName : null,
            'last_name' => $type === 'Person' ? $this->faker->lastName : null,
            'email' => $this->faker->unique()->safeEmail,
            'phone_number' => $this->faker->phoneNumber,
            'company_name' => $type === 'Company' ? $this->faker->company : null,
            'legal_id' => $this->faker->unique()->bothify('??-#######-#'), // e.g., AB-1234567-8
            'address_street' => $this->faker->streetAddress,
            'address_city' => $this->faker->city,
            'address_state' => $this->faker->state,
            'address_postal_code' => $this->faker->postcode,
            'address_country' => $this->faker->country,
            'status' => $this->faker->randomElement(['Active', 'Inactive', 'Lead', 'Prospect']),
            'created_by_user_id' => $this->created_by_user_id ?? CrmUser::inRandomOrder()->first()->user_id ?? CrmUser::factory(),
            // 'notes' => $this->faker->paragraph, // Removed to avoid conflict with
        ];
        
        // Only set these if not already set (allows tests to override)
        if (!isset($this->tenant_id)) {
            $data['tenant_id'] = Tenant::factory();
        }
        
        if (!isset($this->created_by_user_id)) {
            $data['created_by_user_id'] = CrmUser::inRandomOrder()->first()->user_id ?? CrmUser::factory();
        }
        
        return $data;
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->getKey(),
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant),
        ]);
    }
    
    public function configure()
    {
        return $this->afterCreating(function (Customer $customer) {
            // Ensure exactly two contacts exist for the customer (tests expect 2)
            $currentCount = $customer->contacts()->count();
            for ($i = $currentCount; $i < 2; $i++) {
                $customer->contacts()->create([
                    'first_name' => $i === 0 ? ($customer->first_name ?: $this->faker->firstName) : $this->faker->firstName,
                    'last_name' => $i === 0 ? ($customer->last_name ?: $this->faker->lastName) : $this->faker->lastName,
                    'email' => $i === 0 ? ($customer->email ?: $this->faker->unique()->safeEmail) : $this->faker->unique()->safeEmail,
                    'phone' => $i === 0 ? ($customer->phone_number ?: $this->faker->phoneNumber) : $this->faker->phoneNumber,
                    'title' => $this->faker->jobTitle,
                    'created_by_user_id' => $customer->created_by_user_id,
                    'tenant_id' => $customer->tenant_id,
                    'contactable_type' => Customer::class,
                    'contactable_id' => $customer->customer_id
                ]);
            }
        });
    }
    
}