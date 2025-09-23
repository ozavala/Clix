<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CrmUser;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $authUser = Auth::user();
        $tenant = $authUser && $authUser->tenant_id
            ? Tenant::find($authUser->tenant_id)
            : Tenant::factory()->create();

        $customer = Customer::factory()->forTenant($tenant)->create();
        $order = Order::factory()->forTenant($tenant, $customer)->create();
        $createdBy = $authUser && $authUser->tenant_id === ($tenant->id ?? null)
            ? $authUser
            : CrmUser::factory()->forTenant($tenant)->create();

        return [
            'tenant_id' => $tenant->getKey(),
            'order_id' => $order->order_id,
            'invoice_number' => 'INV-' . fake()->unique()->numberBetween(1000, 9999),
            'customer_id' => $customer->customer_id,
            'created_by_user_id' => $createdBy->user_id,
            'invoice_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'subtotal' => fake()->randomFloat(2, 100, 10000),
            'tax_amount' => fake()->randomFloat(2, 10, 1000),
            'discount_amount' => fake()->randomFloat(2, 0, 500),
            'total_amount' => fake()->randomFloat(2, 100, 10000),
            'status' => fake()->randomElement(['draft', 'sent', 'paid', 'overdue', 'cancelled']),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant, ?\App\Models\Customer $customer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->getKey(),
            'order_id' => Order::factory()->forTenant($tenant),
            'customer_id' => $customer ? $customer->customer_id : Customer::factory()->forTenant($tenant),
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant),
        ]);
    }

    /**
     * Indicate that the invoice is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }

    /**
     * Indicate that the invoice is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Indicate that the invoice is draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }
} 