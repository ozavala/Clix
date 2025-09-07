<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\CrmUser;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Opportunity>
 */
class OpportunityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->bs(),
            'description' => $this->faker->sentence(5),
            'lead_id' => Lead::factory(),
            'customer_id' => Customer::factory(),
            'contact_id' => Contact::factory(),
            'stage' => $this->faker->randomElement(array_keys(Opportunity::$stages)),
            'amount' => $this->faker->randomFloat(2, 1000, 100000),
            'expected_close_date' => $this->faker->dateTimeBetween('+1 week', '+6 months')->format('Y-m-d'),
            'probability' => $this->faker->numberBetween(5, 95),
            'assigned_to_user_id' => CrmUser::factory(),
            'created_by_user_id' => CrmUser::factory(),
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'customer_id' => Customer::factory()->forTenant($tenant),
            'lead_id' => Lead::factory()->forTenant($tenant),
            'contact_id' => Contact::factory()->forTenant($tenant),
            'assigned_to_user_id' => CrmUser::factory()->forTenant($tenant),
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant),
        ]);
    }
}