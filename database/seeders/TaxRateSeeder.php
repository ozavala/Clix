<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TaxRate;
use App\Models\CrmUser;

class TaxRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener un usuario para asignar como creador
        $user = CrmUser::first() ?? CrmUser::factory()->create();

        // Tasas de IVA para Ecuador
        TaxRate::create([
            'tenant_id' => 1,
            'name' => 'IVA 0% - Exento',
            'rate' => 0.00,
            'description' => 'Productos y servicios exentos de IVA en Ecuador',
            'country_code' => 'EC',
            'is_active' => true,
        ]);

        TaxRate::create([
            'tenant_id' => 1,
            'name' => 'IVA 15% - General',
            'rate' => 15.00,
            'description' => 'Tasa general de IVA en Ecuador',
            'country_code' => 'EC',
            'is_active' => true,
        ]);

        TaxRate::create([
            'tenant_id' => 1,
            'name' => 'IVA 22% - Especial',
            'rate' => 22.00,
            'description' => 'Tasa especial de IVA en Ecuador (bebidas alcohólicas, cigarrillos, etc.)',
            'country_code' => 'EC',
            'is_active' => true,
        ]);

        // Tasas de IVA para España
        TaxRate::create([
            'tenant_id' => 1,
            'name' => 'IVA 0% - Exento',
            'rate' => 0.00,
            'description' => 'Productos y servicios exentos de IVA en España',
            'country_code' => 'ES',
            'is_active' => true,
        ]);

        TaxRate::create([
            'tenant_id' => 1,
            'name' => 'IVA 4% - Superreducido',
            'rate' => 4.00,
            'description' => 'Tasa superreducida de IVA en España',
            'country_code' => 'ES',
            'is_active' => true,
        ]);

        TaxRate::create([
            'tenant_id' => 1,
            'name' => 'IVA 10% - Reducido',
            'rate' => 10.00,
            'description' => 'Tasa reducida de IVA en España',
            'country_code' => 'ES',
            'is_active' => true,
        ]);

        TaxRate::create([
            'tenant_id' => 1,
            'name' => 'IVA 21% - General',
            'rate' => 21.00,
            'description' => 'Tasa general de IVA en España',
            'country_code' => 'ES',
            'is_active' => true,
        ]);

        // Tasas de IVA para México
        TaxRate::create([
            'tenant_id' => 1,
            'name' => 'IVA 0% - Exento',
            'rate' => 0.00,
            'description' => 'Productos y servicios exentos de IVA en México',
            'country_code' => 'MX',
            'is_active' => true,
        ]);

        TaxRate::create([
            'tenant_id' => 1,
            'name' => 'IVA 16% - General',
            'rate' => 16.00,
            'description' => 'Tasa general de IVA en México',
            'country_code' => 'MX',
            'is_active' => true,
        ]);

        // España (ES)
        $this->createTaxRatesForCountry('ES', $user);

        // México (MX)
        $this->createTaxRatesForCountry('MX', $user);

        // Argentina (AR)
        $this->createTaxRatesForCountry('AR', $user);

        // Colombia (CO)
        $this->createTaxRatesForCountry('CO', $user);
    }

    private function createTaxRatesForCountry(string $countryCode, CrmUser $user): void
    {
        $taxRates = $this->getTaxRatesForCountry($countryCode);

        foreach ($taxRates as $taxRate) {
            TaxRate::create([
                'tenant_id' => 1,
                'name' => $taxRate['name'],
                'rate' => $taxRate['rate'],
                'country_code' => $countryCode,
                'product_type' => $taxRate['product_type'] ?? 'all',
                'description' => $taxRate['description'],
                'is_active' => true,
                'is_default' => $taxRate['is_default'] ?? false,
                'created_by_user_id' => $user->user_id,
            ]);
        }
    }

    private function getTaxRatesForCountry(string $countryCode): array
    {
        return match ($countryCode) {
            'ES' => [
                [
                    'tenant_id' => 1,
                    'name' => 'IVA General',
                    'rate' => 21.00,
                    'description' => 'Tasa general de IVA en España',
                    'product_type' => 'all',
                    'is_default' => true,
                ],
                [
                    'tenant_id' => 1,
                    'name' => 'IVA Reducido',
                    'rate' => 10.00,
                    'description' => 'Tasa reducida para productos básicos',
                    'product_type' => 'goods',
                ],
                [
                    'tenant_id' => 1,
                    'name' => 'IVA Superreducido',
                    'rate' => 4.00,
                    'description' => 'Tasa superreducida para productos de primera necesidad',
                    'product_type' => 'goods',
                ],
                [
                    'tenant_id' => 1,
                    'name' => 'IVA Cero',
                    'rate' => 0.00,
                    'description' => 'Productos exentos de IVA',
                    'product_type' => 'all',
                ],
            ],
            'MX' => [
                [
                    'tenant_id' => 1,
                    'name' => 'IVA General',
                    'rate' => 16.00,
                    'description' => 'Tasa general de IVA en México',
                    'product_type' => 'all',
                    'is_default' => true,
                ],
                [
                    'tenant_id' => 1,
                    'name' => 'IVA Cero',
                    'rate' => 0.00,
                    'description' => 'Productos exentos de IVA',
                    'product_type' => 'all',
                ],
            ],
            'AR' => [
                [
                    'tenant_id' => 1,
                    'name' => 'IVA General',
                    'rate' => 21.00,
                    'description' => 'Tasa general de IVA en Argentina',
                    'product_type' => 'all',
                    'is_default' => true,
                ],
                [
                    'tenant_id' => 1,
                    'name' => 'IVA Reducido',
                    'rate' => 10.50,
                    'description' => 'Tasa reducida para productos básicos',
                    'product_type' => 'goods',
                ],
                [
                    'tenant_id' => 1,
                    'name' => 'IVA Cero',
                    'rate' => 0.00,
                    'description' => 'Productos exentos de IVA',
                    'product_type' => 'all',
                ],
            ],
            'CO' => [
                [
                    'tenant_id' => 1,
                    'name' => 'IVA General',
                    'rate' => 19.00,
                    'description' => 'Tasa general de IVA en Colombia',
                    'product_type' => 'all',
                    'is_default' => true,
                ],
                [
                    'tenant_id' => 1,
                    'name' => 'IVA Reducido',
                    'rate' => 5.00,
                    'description' => 'Tasa reducida para productos básicos',
                    'product_type' => 'goods',
                ],
                [
                    'tenant_id' => 1,
                    'name' => 'IVA Cero',
                    'rate' => 0.00,
                    'description' => 'Productos exentos de IVA',
                    'product_type' => 'all',
                ],
            ],
            default => [
                [
                    'tenant_id' => 1,
                    'name' => 'IVA General',
                    'rate' => 21.00,
                    'description' => 'Tasa general de IVA',
                    'product_type' => 'all',
                    'is_default' => true,
                ],
            ],
        };
    }
}
