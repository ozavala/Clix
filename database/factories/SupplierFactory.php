<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SupplierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->company,
            'contact_person' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone_number' => $this->faker->phoneNumber,
            'noteable_id' => $this->faker->numberBetween(1, 10),
            'noteable_type' => 'App\Models\Supplier',
            'legal_id' => 'SUP-' . strtoupper(Str::random(10)),
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}