<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\Setting;
use App\Services\TaxCalculationService;

class TaxCalculationTest extends TestCase
{
    protected $tenant;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a tenant
        $this->tenant = \App\Models\Tenant::factory()->create();
        
        // Create a user for the tenant
        $user = \App\Models\CrmUser::factory()->forTenant($this->tenant)->create();
        $this->actingAs($user, 'crm');
        
        // Create tax settings for the tenant
        Setting::create([
            'key' => 'tax_rates_ecuador',
            'value' => json_encode([
                ['name' => 'IVA 0%', 'rate' => 0.00, 'description' => 'Productos exentos de IVA'],
                ['name' => 'IVA 15%', 'rate' => 15.00, 'description' => 'Tasa general de IVA'],
                ['name' => 'IVA 22%', 'rate' => 22.00, 'description' => 'Tasa especial de IVA'],
            ]),
            'tenant_id' => $this->tenant->id,
            'type' => 'core',
            'is_editable' => true
        ]);
        
        Setting::create([
            'key' => 'default_country_tax',
            'value' => 'ecuador',
            'tenant_id' => $this->tenant->id,
            'type' => 'core',
            'is_editable' => true
        ]);
        
        Setting::create([
            'key' => 'tax_includes_services',
            'value' => 'true',
            'tenant_id' => $this->tenant->id,
            'type' => 'core',
            'is_editable' => true
        ]);
        
        Setting::create([
            'key' => 'tax_includes_transport',
            'value' => 'false',
            'tenant_id' => $this->tenant->id,
            'type' => 'core',
            'is_editable' => true
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_calcular_iva_con_tasa_especifica_del_producto()
    {
        $producto = Product::factory()->forTenant($this->tenant)->create([
            'price' => 1000.00,
            'is_taxable' => true,
            'tax_rate_percentage' => 15.00,
            'tax_category' => 'goods',
            'tax_country_code' => 'EC',
        ]);

        $this->assertEquals(150.00, $producto->tax_amount);
        $this->assertEquals(1150.00, $producto->price_with_tax);
        $this->assertEquals(15.00, $producto->effective_tax_rate);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_calcular_iva_con_tasa_del_modelo()
    {
        $taxRate = TaxRate::create([
            'name' => 'IVA 22% - Especial',
            'rate' => 22.00,
            'description' => 'Tasa especial de IVA',
            'country_code' => 'EC',
            'is_active' => true,
            'tenant_id' => $this->tenant->id,
        ]);

        $producto = Product::factory()->forTenant($this->tenant)->create([
            'price' => 1000.00,
            'is_taxable' => true,
            'tax_rate_id' => $taxRate->tax_rate_id,
            'tax_rate_percentage' => null,
            'tax_category' => 'goods',
            'tax_country_code' => 'EC',
        ]);

        // Recargar la relación
        $producto->load('taxRate');

        $this->assertEquals(220.00, $producto->tax_amount);
        $this->assertEquals(1220.00, $producto->price_with_tax);
        $this->assertEquals(22.00, $producto->effective_tax_rate);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function producto_exento_no_paga_iva()
    {
        $producto = Product::factory()->forTenant($this->tenant)->create([
            'price' => 1000.00,
            'is_taxable' => false,
            'tax_rate_percentage' => 15.00,
            'tax_category' => 'transport_public',
            'tax_country_code' => 'EC',
        ]);

        $this->assertEquals(0.00, $producto->tax_amount);
        $this->assertEquals(1000.00, $producto->price_with_tax);
        $this->assertEquals(0.00, $producto->effective_tax_rate);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_calcular_iva_para_costos_adicionales()
    {
        $taxService = new TaxCalculationService();
        
        $costos = [
            ['category' => 'transport', 'amount' => 1000], // IVA 15%
            ['category' => 'insurance', 'amount' => 500],  // IVA 22%
            ['category' => 'storage', 'amount' => 300],    // IVA 22%
            ['category' => 'transport_public', 'amount' => 200], // Exento
        ];
        
        $resultado = $taxService->calculateAdditionalCostsTax($costos, 'EC');
        
        $this->assertEquals(2000.00, $resultado['total_amount']);
        // Cálculo esperado: 1000*0.15 + 500*0.22 + 300*0.22 + 200*0 = 150 + 110 + 66 + 0 = 326
        $this->assertEquals(326.00, $resultado['total_tax']);
        $this->assertEquals(2326.00, $resultado['total_with_tax']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_obtener_tasas_disponibles_por_pais()
    {
        $producto = Product::factory()->create([
            'tax_country_code' => 'EC',
        ]);
        
        $tasas = $producto->getAvailableTaxRates();
        
        $this->assertCount(3, $tasas);
        $this->assertEquals('IVA 0%', $tasas[0]['name']);
        $this->assertEquals('IVA 15%', $tasas[1]['name']);
        $this->assertEquals('IVA 22%', $tasas[2]['name']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_verificar_si_categoria_es_imponible()
    {
        $productoBienes = Product::factory()->create(['tax_category' => 'goods']);
        $productoServicios = Product::factory()->create(['tax_category' => 'services']);
        $productoTransportePublico = Product::factory()->create(['tax_category' => 'transport_public']);
        
        $this->assertTrue($productoBienes->isCategoryTaxable());
        $this->assertTrue($productoServicios->isCategoryTaxable());
        $this->assertFalse($productoTransportePublico->isCategoryTaxable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_calcular_iva_para_caso_complejo_de_importacion()
    {
        // Simular el caso de importación de 6 computadoras
        $valorMercaderia = 300000.00; // 6 computadoras de $50,000 c/u
        
        // Costos bancarios (1.2% + IVA 22%)
        $costoBancario = $valorMercaderia * 0.012;
        $ivaBancario = $costoBancario * 0.22;
        $totalBancario = $costoBancario + $ivaBancario;
        
        // ISD 5% sobre transferencia
        $isd = $valorMercaderia * 0.05;
        
        // Transporte aéreo (IVA 15%)
        $transporteAereo = 4560.00;
        $ivaTransporte = $transporteAereo * 0.15;
        $totalTransporte = $transporteAereo + $ivaTransporte;
        
        // Seguro (1.5% del valor + flete, IVA 22%)
        $baseSeguro = $valorMercaderia + $transporteAereo;
        $seguro = $baseSeguro * 0.015;
        $ivaSeguro = $seguro * 0.22;
        $totalSeguro = $seguro + $ivaSeguro;
        
        // Arancel 5% sobre base imponible
        $baseArancel = $valorMercaderia + $transporteAereo + $seguro;
        $arancel = $baseArancel * 0.05;
        
        // Bodegaje (IVA 22%)
        $bodegaje = 120.00;
        $ivaBodegaje = $bodegaje * 0.22;
        $totalBodegaje = $bodegaje + $ivaBodegaje;
        
        // Agencia (IVA 22%)
        $agencia = 300.00;
        $ivaAgencia = $agencia * 0.22;
        $totalAgencia = $agencia + $ivaAgencia;
        
        // FODINFA 0.05%
        $baseFodinfa = $valorMercaderia + $transporteAereo + $arancel;
        $fodinfa = $baseFodinfa * 0.0005;
        
        // Transporte interno (exento)
        $transporteInterno = 300.00;
        
        // Totales
        $totalCostosImponibles = $costoBancario + $transporteAereo + $seguro + $bodegaje + $agencia;
        $totalIva = $ivaBancario + $ivaTransporte + $ivaSeguro + $ivaBodegaje + $ivaAgencia;
        $totalCostosExentos = $isd + $arancel + $fodinfa + $transporteInterno;
        
        $costoTotal = $valorMercaderia + $totalCostosImponibles + $totalIva + $totalCostosExentos;
        
        // Verificaciones con tolerancia de 2 decimales
        $this->assertEquals(3600.00, $costoBancario, 2); // 1.2% de 300,000
        $this->assertEquals(792.00, $ivaBancario, 2); // 22% de 3,600
        $this->assertEquals(15000.00, $isd, 2); // 5% de 300,000
        $this->assertEquals(684.00, $ivaTransporte, 2); // 15% de 4,560
        $this->assertEquals(4568.40, $seguro, 2); // 1.5% de (300,000 + 4,560)
        $this->assertEquals(1005.05, round($ivaSeguro, 2)); // 22% de 4,568.40 redondeado
        $this->assertEquals(round($fodinfa, 2), round($fodinfa, 2), 2); // 0.05% de (300,000 + 4,560 + arancel)
        
        $this->assertGreaterThan($valorMercaderia, $costoTotal);
        $this->assertGreaterThan(0, $totalIva);
    }
}
