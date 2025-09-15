<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\CrmUser; // Use CrmUser for created_by
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $authUser = Auth::user();
        $tenant = $authUser && $authUser->tenant_id
            ? Tenant::find($authUser->tenant_id)
            : Tenant::factory()->create();

        $bill = Bill::factory()->forTenant($tenant)->create();
        $createdBy = $authUser && $authUser->tenant_id === ($tenant->id ?? null)
            ? $authUser
            : CrmUser::factory()->forTenant($tenant)->create();

        return [
            'tenant_id' => $tenant->id,
            'payment_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'notes' => $this->faker->optional()->sentence,
            'payable_id' => $bill->bill_id,
            'payable_type' => Bill::class,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'created_by_user_id' => $createdBy->user_id,
        ];
    }

    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'payable_id' => Bill::factory()->forTenant($tenant)->create()->bill_id,
            'created_by_user_id' => CrmUser::factory()->forTenant($tenant)->create()->user_id,
        ]);
    }
}

