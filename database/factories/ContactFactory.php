<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\CrmUser;
use App\Models\Tenant;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
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
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'title' => $this->faker->jobTitle(),
            // contactable_id/type will be filled by relationship factory (hasContacts)
            'created_by_user_id' => CrmUser::factory(),
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant),
        ]);
    }
}