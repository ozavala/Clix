<?php

namespace Database\Factories;

use App\Models\CrmUser;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
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
            'body' => $this->faker->paragraph,
            'noteable_id' => function (array $attributes) {
                return Customer::factory()->forTenant(Tenant::find($attributes['tenant_id']))->create()->customer_id;
            },
            'noteable_type' => Customer::class,
            'created_by_user_id' => function (array $attributes) {
                return CrmUser::factory()->forTenant(Tenant::find($attributes['tenant_id']))->create()->user_id;
            },
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'noteable_id' => Customer::factory()->forTenant($tenant),
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant),
        ]);
    }
}
