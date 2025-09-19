<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaxSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear un usuario para las pruebas
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_actualizar_tasas_de_iva_para_un_pais()
    {
        $rates = [
            [
                'name' => 'IVA 0%',
                'rate' => 0.00,
                'description' => 'Productos exentos'
            ],
            [
                'name' => 'IVA 15%',
                'rate' => 15.00,
                'description' => 'Tasa general'
            ]
        ];

        $response = $this->post('/tax-settings/EC/rates', [
            'rates' => $rates
        ]);

        $response->assertRedirect('/tax-settings');
        $response->assertSessionHas('success');

        // Verificar que se guardó correctamente
        $setting = Setting::where('key', 'tax_rates_EC')->first();
        $this->assertNotNull($setting);
        $this->assertEquals('custom', $setting->type);
        $this->assertEquals(json_encode($rates), $setting->value);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_establecer_pais_por_defecto()
    {
        $response = $this->post('/tax-settings/default-country', [
            'country_code' => 'ES'
        ]);

        $response->assertRedirect('/tax-settings');
        $response->assertSessionHas('success');

        // Verificar que se guardó correctamente
        $setting = Setting::where('key', 'default_country_tax')->first();
        $this->assertNotNull($setting);
        $this->assertEquals('custom', $setting->type);
        $this->assertEquals('ES', $setting->value);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_actualizar_configuraciones_de_servicios()
    {
        $response = $this->post('/tax-settings/service-settings', [
            'tax_includes_services' => true,
            'tax_includes_transport' => false
        ]);

        $response->assertRedirect('/tax-settings');
        $response->assertSessionHas('success');

        // Verificar que se guardaron correctamente
        $servicesSetting = Setting::where('key', 'tax_includes_services')->first();
        $transportSetting = Setting::where('key', 'tax_includes_transport')->first();

        $this->assertNotNull($servicesSetting);
        $this->assertNotNull($transportSetting);
        $this->assertEquals('custom', $servicesSetting->type);
        $this->assertEquals('custom', $transportSetting->type);
        $this->assertEquals('true', $servicesSetting->value);
        $this->assertEquals('false', $transportSetting->value);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_restaurar_tasas_por_defecto()
    {
        $response = $this->post('/tax-settings/ES/restore-defaults');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verificar que se guardó correctamente
        $setting = Setting::where('key', 'tax_rates_ES')->first();
        $this->assertNotNull($setting);
        $this->assertEquals('custom', $setting->type);
        
        $rates = json_decode($setting->value, true);
        $this->assertIsArray($rates);
        $this->assertNotEmpty($rates);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_mostrar_pagina_de_configuraciones()
    {
        $response = $this->get('/tax-settings');

        $response->assertStatus(200);
        $response->assertViewIs('tax_settings.index');
        $response->assertViewHas('taxSettings');
        $response->assertViewHas('countries');
        $response->assertViewHas('defaultCountry');
        $response->assertViewHas('serviceSettings');
    }
} 