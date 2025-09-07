<?php

namespace Database\Factories;

use App\Models\TaxCollection;
use App\Models\TaxRate;
use App\Models\Invoice;
use App\Models\CrmUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxCollection>
 */
class TaxCollectionFactory extends Factory
{
    protected $model = TaxCollection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taxRate = TaxRate::where('is_active', true)->inRandomOrder()->first();
        $taxableAmount = $this->faker->randomFloat(2, 500, 25000);
        $taxAmount = $taxableAmount * ($taxRate->rate / 100);

        return [
            'invoice_id' => Invoice::factory(),
            'tax_rate_id' => $taxRate->tax_rate_id,
            'taxable_amount' => $taxableAmount,
            'tax_amount' => $taxAmount,
            'collection_type' => $this->faker->randomElement(['sale', 'service']),
            'collection_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'customer_name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['collected', 'pending', 'refunded']),
            'remittance_date' => $this->faker->optional(0.4)->dateTimeBetween('-1 year', 'now'),
            'created_by_user_id' => CrmUser::factory(),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => Invoice::factory()->forTenant($tenant),
            'tax_rate_id' => TaxRate::factory()->forTenant($tenant),
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant),
        ]);
    }

    /**
     * Indicate that the tax collection is for sales.
     */
    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_type' => 'sale',
            'description' => 'IVA cobrado en venta',
        ]);
    }

    /**
     * Indicate that the tax collection is for services.
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_type' => 'service',
            'description' => 'IVA cobrado en servicio',
        ]);
    }

    /**
     * Indicate that the tax collection is remitted.
     */
    public function remitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'remitted',
            'remittance_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
