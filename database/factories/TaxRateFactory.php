<?php

namespace Database\Factories;

use App\Models\TaxRate;
use App\Models\CrmUser;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxRate>
 */
class TaxRateFactory extends Factory
{
    protected $model = TaxRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rates = [
            ['name' => 'IVA General', 'rate' => 15.00, 'description' => 'Tasa general de IVA'],
            ['name' => 'IVA Reducido', 'rate' => 10.00, 'description' => 'Tasa reducida para productos básicos'],
            ['name' => 'IVA Superreducido', 'rate' => 4.00, 'description' => 'Tasa superreducida para productos de primera necesidad'],
            ['name' => 'IVA Cero', 'rate' => 0.00, 'description' => 'Productos exentos de IVA'],
        ];

        $selectedRate = $this->faker->randomElement($rates);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $selectedRate['name'],
            'rate' => $selectedRate['rate'],
            'country_code' => $this->faker->randomElement(['ES', 'MX', 'AR', 'CO']),
            'product_type' => $this->faker->randomElement(['goods', 'services', 'all']),
            'description' => $selectedRate['description'],
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'is_default' => $selectedRate['rate'] === 21.00, // IVA General como default
            'created_by_user_id' => function (array $attributes) {
                return CrmUser::factory()->forTenant($attributes['tenant_id'])->create()->user_id;
            },
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant),
        ]);
    }

    /**
     * Indicate that the tax rate is the default one.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'IVA General',
            'rate' => 21.00,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the tax rate is for goods only.
     */
    public function forGoods(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'goods',
        ]);
    }

    /**
     * Indicate that the tax rate is for services only.
     */
    public function forServices(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'services',
        ]);
    }
}

