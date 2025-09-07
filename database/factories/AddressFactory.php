<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
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
            'addressable_type' => Customer::class,
            'addressable_id' => Customer::factory(),
            'address_type' => fake()->randomElement(['billing', 'shipping', 'main']),
            'street_address_line_1' => fake()->streetAddress(),
            'street_address_line_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state_province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => fake()->randomElement(['US', 'CA', 'MX', 'GB', 'DE', 'FR']),
            'is_primary' => fake()->boolean(20), // 20% chance of being primary
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Indicate that the address is for a customer.
     */
    public function forCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'addressable_type' => Customer::class,
            'addressable_id' => Customer::factory()->forTenant($attributes['tenant_id']),
        ]);
    }

    /**
     * Indicate that the address is for a supplier.
     */
    public function forSupplier(): static
    {
        return $this->state(fn (array $attributes) => [
            'addressable_type' => Supplier::class,
            'addressable_id' => Supplier::factory()->forTenant($attributes['tenant_id']),
        ]);
    }

    /**
     * Indicate that the address is for a warehouse.
     */
    public function forWarehouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'addressable_type' => Warehouse::class,
            'addressable_id' => Warehouse::factory()->forTenant($attributes['tenant_id']),
        ]);
    }

    /**
     * Indicate that the address is a billing address.
     */
    public function billing(): static
    {
        return $this->state(fn (array $attributes) => [
            'address_type' => 'billing',
        ]);
    }

    /**
     * Indicate that the address is a shipping address.
     */
    public function shipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'address_type' => 'shipping',
        ]);
    }

    /**
     * Indicate that the address is primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
 