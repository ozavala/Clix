<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LandedCost>
 */
class LandedCostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'costable_type' => 'App\\Models\\PurchaseOrder',
            'costable_id' => PurchaseOrder::factory(),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 10, 1000),
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'costable_id' => PurchaseOrder::factory()->forTenant($tenant),
        ]);
    }

    /**
     * Indicate that the landed cost is for freight.
     */
    public function freight(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_type' => 'freight',
            'description' => 'Freight charges',
        ]);
    }

    /**
     * Indicate that the landed cost is for insurance.
     */
    public function insurance(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_type' => 'insurance',
            'description' => 'Insurance charges',
        ]);
    }

    /**
     * Indicate that the landed cost is for customs.
     */
    public function customs(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_type' => 'customs',
            'description' => 'Customs duties',
        ]);
    }
} 