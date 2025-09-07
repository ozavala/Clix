<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CrmUser;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
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
            'order_number' => 'ORD-' . fake()->unique()->numberBetween(1000, 9999),
            'customer_id' => Customer::factory(),
            'created_by_user_id' => CrmUser::factory(),
            'order_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'order_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'subtotal' => fake()->randomFloat(2, 100, 10000),
            'tax_amount' => fake()->randomFloat(2, 10, 1000),
            'discount_amount' => fake()->randomFloat(2, 0, 500),
            'total_amount' => fake()->randomFloat(2, 100, 10000),
            'status' => fake()->randomElement(['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled']),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'customer_id' => Customer::factory()->forTenant($tenant),
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant),
        ]);
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the order is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Indicate that the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
} 