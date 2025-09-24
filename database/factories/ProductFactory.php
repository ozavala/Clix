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
        $taxRates = [0, 15, 22];
        
        // Prefer authenticated user's tenant (crm or default guard), then currentTenant, then first/new tenant
        $authCrm = auth('crm')->user();
        $authWeb = auth()->user();
        $tenant = null;
        if ($authCrm && $authCrm->tenant_id) {
            $tenant = Tenant::find($authCrm->tenant_id);
        } elseif ($authWeb && method_exists($authWeb, 'tenant_id') && $authWeb->tenant_id) {
            $tenant = Tenant::find($authWeb->tenant_id);
        }
        if (!$tenant) {
            $tenant = (app()->bound('currentTenant') && app('currentTenant'))
                ? app('currentTenant')
                : (Tenant::first() ?? Tenant::factory()->create());
        }
        $tenantId = $tenant->tenant_id ?? $tenant->id;
        
        // Get or create a user for the tenant
        $user = CrmUser::where('tenant_id', $tenantId)->first() 
            ?: CrmUser::factory()->forTenant($tenant)->create();

        return [
            'tenant_id' => $tenantId,
            'name' => fake()->words(2, true),
            'description' => fake()->paragraph(),
            'sku' => fake()->unique()->regexify('[A-Z]{2}[0-9]{6}'),
            'price' => fake()->randomFloat(2, 1, 1000),
            'cost' => fake()->randomFloat(2, 0.5, 500),
            'quantity_on_hand' => $isService ? 0 : fake()->numberBetween(0, 100),
            'reorder_point' => $isService ? 0 : fake()->numberBetween(5, 20),
            'is_service' => $isService,
            'is_active' => true,
            'created_by_user_id' => $user->user_id,
            'category_id' => ProductCategory::factory()->forTenant($tenant)->create()->category_id,
            'tax_rate_id' => TaxRate::factory()->forTenant($tenant)->create()->tax_rate_id,
            'is_taxable' => fake()->boolean(80),
            'tax_rate_percentage' => fake()->randomElement($taxRates),
            'tax_category' => fake()->randomElement($taxCategories),
            'tax_country_code' => 'EC',
        ];
    }

    public function forTenant(Tenant $tenant, ?ProductCategory $category = null): static
    {
        // Get or create a user for the tenant
        $user = CrmUser::where('tenant_id', $tenant->tenant_id)->first() 
            ?: CrmUser::factory()->forTenant($tenant)->create();

        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->tenant_id,
            'created_by_user_id' => $user->user_id,
            'category_id' => $category 
                ? $category->category_id 
                : ProductCategory::factory()->forTenant($tenant)->create()->category_id,
            'tax_rate_id' => TaxRate::factory()->forTenant($tenant)->create()->tax_rate_id,
        ]);
    }
}