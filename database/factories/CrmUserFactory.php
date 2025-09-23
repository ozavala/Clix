<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CrmUser>
 */
class CrmUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $username = strtolower($firstName[0] . $lastName . $this->faker->randomNumber(2));
        
        return [
            'user_id' => User::factory(),
            'tenant_id' => function () {
            return Tenant::factory()->create()->tenant_id;
        },
        'username' => $this->faker->unique()->userName,
        'full_name' => $this->faker->name,
        'email' => $this->faker->unique()->safeEmail,
        'email_verified_at' => now(),
        'password' => bcrypt('password'),
        'locale' => $this->faker->randomElement(['es', 'en']),
        ];
    }

    public function forTenant(Tenant $tenant)
{
    return $this->state(fn (array $attributes) => [
        'tenant_id' => $tenant->tenant_id,
    ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_super_admin' => true,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
