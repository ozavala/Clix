<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Models\Tenant;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*global $nTenants;
        $nTenants = DB::table('tenants')->count();
        $ntenant = $nTenants + 1;*/
                   
        // Core settings
        $coreSettings = [
            
            ['key' => 'name', 'value' =>'La Empresa S.A:', 'type' => 'core', 'is_editable' => false],
            ['key' => 'legal_id', 'value' =>'0992793747-001', 'type' => 'core', 'is_editable' => false],
            ['key' => 'isActive', 'value' => 1, 'type' => 'core', 'is_editable' => false],
            ['key' => 'address', 'value' =>'Av. 24 de Julio 123', 'type' => 'core', 'is_editable' => false],
            ['key' => 'phone', 'value' => '12345678', 'type' => 'core', 'is_editable' => false],
            ['key' => 'website', 'value' => 'https://empresa.com', 'type' => 'core', 'is_editable' => false],
            ['key' => 'logo', 'value' => 'logo.png', 'type' => 'core', 'is_editable' => false],
            ['key' => 'email', 'value' => 'info@empresa.com', 'type' => 'core', 'is_editable' => false],
            ['key' => 'industry', 'value' => 'Industria', 'type' => 'core', 'is_editable' => false],
            ['key' => 'default_locale', 'value' => 'es', 'type' => 'core', 'is_editable' => true],
            ['key' => 'default_currency', 'value' => 'USD', 'type' => 'core', 'is_editable' => true],
            ['key' => 'tax_includes_services', 'value' => 'true', 'type' => 'core', 'is_editable' => true],
            ['key' => 'tax_includes_transport', 'value' => 'false', 'type' => 'core', 'is_editable' => true],
        ];
               
        foreach ($coreSettings as $setting) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Custom settings de ejemplo
        \App\Models\Setting::updateOrCreate(
            ['key' => 'custom_message'],
            [
                'key' => 'custom_message',
                'value' => 'Bienvenido a Clix',
                'type' => 'custom',
                'is_editable' => true,
            ]
        );
    }
}