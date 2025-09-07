<?php

namespace Database\Seeders;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Tenant;
use App\Models\Supplier;
use App\Models\Customer;

class TransactionSeeder extends Seeder
{
    public function run()
    {
        // Asegúrate de tener datos base
        $tenants = Tenant::all();
        $suppliers = Supplier::all();
        $customers = Customer::all();

        // Si no hay datos, créalos
        if ($tenants->isEmpty()) {
            $tenants = Tenant::factory(3)->create();
        }
        if ($suppliers->isEmpty()) {
            $suppliers = Supplier::factory(5)->create();
        }
        if ($customers->isEmpty()) {
            $customers = Customer::factory(5)->create();
        }

        // Crea transacciones de ejemplo
        foreach (range(1, 20) as $i) {
            Transaction::factory()->create([
                'tenant_id' => $tenants->random()->id,
                'supplier_id'      => $suppliers->random()->id,
                'customer_id'      => $customers->random()->id,
            ]);
        }
    }
}
