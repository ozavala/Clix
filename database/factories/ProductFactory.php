<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\CrmUser;
use App\Models\TaxRate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $isService = fake()->boolean(30);
        $taxCategories = ['goods', 'services', 'transport', 'insurance', 'storage', 'transport_public'];
        $taxRates = [0, 15, 22]; // Tasas de IVA para Ecuador
        
        // Prefer the authenticated user's tenant during tests, fallback to a new tenant
        $authUser = Auth::user();
        $tenant = $authUser && $authUser->tenant_id
            ? Tenant::find($authUser->tenant_id)
            : Tenant::factory()->create();

        return [
            'tenant_id' => $tenant->id, // Use the created tenant's ID
            'name' => fake()->words(2, true),
            'description' => fake()->paragraph(),
            'sku' => fake()->unique()->regexify('[A-Z]{2}[0-9]{6}'),
            'price' => fake()->randomFloat(2, 10, 1000),
            'cost' => fake()->randomFloat(2, 5, 800),
            'quantity_on_hand' => $isService ? 0 : fake()->numberBetween(0, 100),
            'reorder_point' => $isService ? 0 : fake()->numberBetween(5, 20),
            'is_service' => $isService,
            'is_active' => true,
            'created_by_user_id' => $authUser && $authUser->tenant_id === ($tenant->id ?? null)
                ? $authUser->user_id
                : CrmUser::factory()->forTenant($tenant)->create()->user_id, // Pass tenant to CrmUser
            'category_id' => ProductCategory::factory()->forTenant($tenant)->create()->category_id, // Pass tenant to ProductCategory
            'tax_rate_id' => TaxRate::factory()->forTenant($tenant)->create()->tax_rate_id, // Pass tenant to TaxRate
            'is_taxable' => fake()->boolean(80), // 80% de productos pagan IVA
            'tax_rate_percentage' => fake()->randomElement($taxRates),
            'tax_category' => fake()->randomElement($taxCategories),
            'tax_country_code' => 'EC', // Ecuador por defecto
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant, ?\App\Models\ProductCategory $category = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant)->create()->user_id,
            'category_id' => $category ? $category->category_id : ProductCategory::factory()->forTenant($tenant)->create()->category_id,
            'tax_rate_id' => TaxRate::factory()->forTenant($tenant)->create()->tax_rate_id,
        ]);
    }
}