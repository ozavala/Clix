<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillItemFactory extends Factory
{
    protected $model = BillItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $unitPrice = $this->faker->randomFloat(2, 10, 100);

        return [
            'bill_id' => Bill::factory(),
            'product_id' => Product::factory(),
            'item_name' => $this->faker->words(3, true),
            'item_description' => $this->faker->sentence,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'item_total' => $quantity * $unitPrice,
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'bill_id' => Bill::factory()->forTenant($tenant),
            'product_id' => Product::factory()->forTenant($tenant),
        ]);
    }
}