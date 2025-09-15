<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\CrmUser;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

class BillFactory extends Factory
{
    protected $model = Bill::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 1000);
        $taxAmount = $subtotal * 0.1; // example tax
        $totalAmount = $subtotal + $taxAmount;

        $authUser = Auth::user();
        $tenant = $authUser && $authUser->tenant_id
            ? Tenant::find($authUser->tenant_id)
            : Tenant::factory()->create();

        $supplier = Supplier::factory()->forTenant($tenant)->create();
        $po = PurchaseOrder::factory()->forTenant($tenant)->create();
        $createdBy = $authUser && $authUser->tenant_id === ($tenant->id ?? null)
            ? $authUser
            : CrmUser::factory()->forTenant($tenant)->create();

        return [
            'tenant_id' => $tenant->id,
            'purchase_order_id' => $po->purchase_order_id,
            'supplier_id' => $supplier->supplier_id,
            'bill_number' => 'BILL-' . $this->faker->unique()->numerify('######'),
            'bill_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'amount_paid' => 0.00,
            'status' => 'Awaiting Payment',
            'notes' => $this->faker->optional()->sentence,
            'created_by_user_id' => $createdBy->user_id,
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}