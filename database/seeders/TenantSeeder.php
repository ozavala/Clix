<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;

class TenantSeeder extends Seeder
{
    public function run()
    {
        // Crea 3 tenants de ejemplo
        Tenant::factory()->count(3)->create();
    }
}