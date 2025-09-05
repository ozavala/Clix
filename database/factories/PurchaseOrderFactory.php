<?php

namespace Database\Factories;

use App\Models\CrmUser;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        $warehouse = Warehouse::with('addresses')->inRandomOrder()->first() ?? Warehouse::factory()->hasAddresses()->create();
        $shippingAddress = $warehouse->addresses()->where('is_primary', true)->first() ?? $warehouse->addresses()->first();

        return [
            'supplier_id' => Supplier::factory(),
            'shipping_address_id' => $shippingAddress?->address_id,
            'purchase_order_number' => $this->faker->unique()->bothify('PO-????-############'),
            'order_date' => $this -> faker->dateTimeBetween('-2 months', '-1 week'),
            'expected_delivery_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'type' => $this->faker->randomElement(array_keys(PurchaseOrder::$types)),
            'status' => 'draft', // Start with draft status
            'terms_and_conditions' => 'Net 30. All items subject to inspection upon delivery.',
            'notes' => $this->faker->optional()->paragraph,
            'created_by_user_id' => CrmUser::factory(),
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_percentage' => $this->faker->randomElement([0, 5, 10]),
            'tax_amount' => 0,
            'shipping_cost' => $this->faker->randomFloat(2, 20, 100),
            'other_charges' => 0,
            'total_amount' => 0,
            'amount_paid' => 0,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (PurchaseOrder $po) {
            if ($po->items->isEmpty()) {
                PurchaseOrderItem::factory(rand(1, 5))->create(['purchase_order_id' => $po->purchase_order_id]);
                $po->load('items');
            }

           $subtotal = $po->items->sum('item_total');
            $discountAmount = 0; // For now, no discount logic in factory
            $subtotalAfterDiscount = $subtotal - $discountAmount;
            $taxAmount = ($subtotalAfterDiscount * $po->tax_percentage) / 100;
            $totalAmount = $subtotalAfterDiscount + $taxAmount + $po->shipping_cost + $po->other_charges;

            $po->updateQuietly([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);
        });
    }

    /**
     * Create a confirmed purchase order.
     */
    public function confirmed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'confirmed',
            ];
        });
    }

    /**
     * Create a ready for dispatch purchase order.
     */
    public function readyForDispatch()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'ready_for_dispatch',
            ];
        });
    }

    /**
     * Create a dispatched purchase order.
     */
    public function dispatched()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'dispatched',
            ];
        });
    }

    /**
     * Create a cancelled purchase order.
     */
    public function cancelled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
            ];
        });
    }
}
