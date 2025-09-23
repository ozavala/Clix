<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Create a default tenant if none exists
        $tenant = \App\Models\Tenant::first() ?? \App\Models\Tenant::factory()->create([
            'name' => 'Default Tenant',
            'email' => 'admin@example.com',
        ]);

        // Create additional tenants if needed
        /*if (\App\Models\Tenant::count() < 5) {
            \App\Models\Tenant::factory()->count(5 - \App\Models\Tenant::count())->create();
        }*/
    }
}