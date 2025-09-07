<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\Order;
use App\Models\CrmUser;
use App\Models\OrderItem;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
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
        $item_total = $quantity * $unit_price;

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'item_total' => $item_total,
            'item_name' => fake()->words(2, true),
            'item_description' => fake()->optional()->sentence(),
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => Order::factory()->forTenant($tenant)->create()->order_id,
            'product_id' => Product::factory()->forTenant($tenant)->create()->product_id,
        ]);
    }
}
