<?php

namespace Database\Factories;

use App\Models\CrmUser;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
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
            'title' => $this->faker->catchPhrase(),
            'description' => $this->faker->paragraph,
            'value' => $this->faker->randomFloat(2, 500, 20000),
            'status' => $this->faker->randomElement(['New', 'Contacted', 'Qualified']),
            'source' => $this->faker->randomElement(['Website', 'Referral', 'Cold Call']),
            'customer_id' => Customer::factory(),
            'assigned_to_user_id' => CrmUser::factory(),
            'created_by_user_id' => CrmUser::factory(),
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'customer_id' => Customer::factory()->forTenant($tenant),
            'assigned_to_user_id' => CrmUser::factory()->forTenant($tenant),
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant),
        ]);
    }
}
