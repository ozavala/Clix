<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unit_price = fake()->randomFloat(2, 10, 1000);
        $total = $quantity * $unit_price;

        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => Product::factory()->create()->product_id,
            'item_name' => fake()->words(2, true),
            'item_description' => fake()->optional()->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'item_total' => $total,
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => Invoice::factory()->forTenant($tenant),
            'product_id' => Product::factory()->forTenant($tenant),
        ]);
    }
} 