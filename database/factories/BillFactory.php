<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\CrmUser;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillFactory extends Factory
{
    protected $model = Bill::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 1000);
        $taxAmount = $subtotal * 0.1; // example tax
        $totalAmount = $subtotal + $taxAmount;

        return [
            'tenant_id' => Tenant::factory(),
            'purchase_order_id' => PurchaseOrder::factory(),
            'supplier_id' => Supplier::factory(),
            'bill_number' => 'BILL-' . $this->faker->unique()->numerify('######'),
            'bill_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'amount_paid' => 0.00,
            'status' => 'Awaiting Payment',
            'notes' => $this->faker->optional()->sentence,
            'created_by_user_id' => CrmUser::factory(),
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}