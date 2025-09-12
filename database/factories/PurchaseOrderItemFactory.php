<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    public function definition(): array
    {
        return [
            'tenant_id' => \App\Models\Tenant::factory(),
            'purchase_order_id' => \App\Models\PurchaseOrder::factory(),
            'product_id' => \App\Models\Product::factory(),
            'item_name' => $this->faker->words(3, true),
            'item_description' => $this->faker->optional()->sentence(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->randomFloat(2, 10, 1000),
            'item_total' => $this->faker->randomFloat(2, 100, 10000),
            'landed_cost_per_unit' => $this->faker->optional()->randomFloat(4, 0, 100),
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'purchase_order_id' => PurchaseOrder::factory()->forTenant($tenant),
            'product_id' => Product::factory()->forTenant($tenant),
        ]);
    }
}