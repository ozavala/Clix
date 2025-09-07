<?php

namespace Database\Factories;

use App\Models\GoodsReceiptItem;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsReceiptItemFactory extends Factory
{
    protected $model = GoodsReceiptItem::class;

    public function definition(): array
    {
        $purchaseOrderItem = PurchaseOrderItem::factory()->create();
        $quantityReceived = $this->faker->numberBetween(1, $purchaseOrderItem->quantity);
        $unitCostWithLanded = $purchaseOrderItem->unit_price + $this->faker->randomFloat(2, 5, 20);

        return [
            'goods_receipt_id' => GoodsReceipt::factory(),
            'purchase_order_item_id' => $purchaseOrderItem->purchase_order_item_id,
            'product_id' => $purchaseOrderItem->product_id,
            'quantity_received' => $quantityReceived,
            'unit_cost_with_landed' => $unitCostWithLanded,
            'total_cost' => $quantityReceived * $unitCostWithLanded,
            'notes' => $this->faker->optional()->sentence,
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'goods_receipt_id' => GoodsReceipt::factory()->forTenant($tenant),
            'purchase_order_item_id' => PurchaseOrderItem::factory()->forTenant($tenant),
            'product_id' => Product::factory()->forTenant($tenant),
        ]);
    }
} 