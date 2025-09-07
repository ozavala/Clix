<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tenant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quotation>
 */
class QuotationFactory extends Factory
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
            'opportunity_id' => null, // Will be set in the seeder
            'subject' => $this->faker->sentence(3),
            'quotation_date' => now(),
            'expiry_date' => now()->addDays(30),
            'status' => 'Draft',
            'subtotal' => $this->faker->randomFloat(2, 100, 10000),
            'discount_type' => 'Percentage',
            'discount_value' => $this->faker->numberBetween(5, 20), // Random discount between 5% and 20%
            'discount_amount' => function (array $attributes) {
                return $attributes['subtotal'] * ($attributes['discount_value'] / 100);
            },
            'tax_percentage' => 15, // Example tax percentage
            'tax_amount' => function (array $attributes) {
                return ($attributes['subtotal'] - $attributes['discount_amount']) * ($attributes['tax_percentage'] / 100);
            },
            'total_amount' => function (array $attributes) {
                return $attributes['subtotal'] - $attributes['discount_amount'] + $attributes['tax_amount'];
            },
            'terms_and_conditions' => $this->faker->paragraph,
            'notes' => $this->faker->paragraph,
            'created_by_user_id' => null, // Will be set in the seeder
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'opportunity_id' => Opportunity::factory()->forTenant($tenant),
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant),
        ]);
    }
}
