<?php

namespace Database\Factories;

use App\Models\TaxPayment;
use App\Models\TaxRate;
use App\Models\PurchaseOrder;
use App\Models\CrmUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxPayment>
 */
class TaxPaymentFactory extends Factory
{
    protected $model = TaxPayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taxRate = TaxRate::where('is_active', true)->inRandomOrder()->first();
        $taxableAmount = $this->faker->randomFloat(2, 1000, 50000);
        $taxAmount = $taxableAmount * ($taxRate->rate / 100);

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'tax_rate_id' => $taxRate->tax_rate_id,
            'taxable_amount' => $taxableAmount,
            'tax_amount' => $taxAmount,
            'payment_type' => $this->faker->randomElement(['import', 'purchase', 'service']),
            'payment_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'document_number' => 'FAC-' . $this->faker->unique()->numberBetween(1000, 9999),
            'supplier_name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['paid', 'pending', 'recovered']),
            'recovery_date' => $this->faker->optional(0.3)->dateTimeBetween('-1 year', 'now'),
            'created_by_user_id' => CrmUser::factory(),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'purchase_order_id' => PurchaseOrder::factory()->forTenant($tenant),
            'tax_rate_id' => TaxRate::factory()->forTenant($tenant),
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant),
        ]);
    }

    /**
     * Indicate that the tax payment is for imports.
     */
    public function import(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'import',
            'description' => 'IVA pagado en importación',
        ]);
    }

    /**
     * Indicate that the tax payment is for purchases.
     */
    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'purchase',
            'description' => 'IVA pagado en compra local',
        ]);
    }

    /**
     * Indicate that the tax payment is recovered.
     */
    public function recovered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'recovered',
            'recovery_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
