<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar settings básicos
        Setting::updateOrCreate([
            'key' => 'tax_rates_ecuador'
        ], [
            'value' => json_encode([
                ['name' => 'IVA 0%', 'rate' => 0.00, 'description' => 'Productos exentos de IVA'],
                ['name' => 'IVA 15%', 'rate' => 15.00, 'description' => 'Tasa general de IVA'],
                ['name' => 'IVA 22%', 'rate' => 22.00, 'description' => 'Tasa especial de IVA'],
            ])
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function producto_con_tasa_especifica_calcula_iva_correctamente()
    {
        $producto = new Product([
            'price' => 1000.00,
            'is_taxable' => true,
            'tax_rate_percentage' => 15.00,
        ]);

        $this->assertEquals(150.00, $producto->tax_amount);
        $this->assertEquals(1150.00, $producto->price_with_tax);
        $this->assertEquals(15.00, $producto->effective_tax_rate);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function producto_con_tasa_del_modelo_calcula_iva_correctamente()
    {
        $taxRate = TaxRate::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'IVA 22% - Especial',
            'rate' => 22.00,
            'description' => 'Tasa especial de IVA',
            'country_code' => 'EC',
            'is_active' => true,
        ]);

        $producto = new Product([
            'price' => 1000.00,
            'is_taxable' => true,
            'tax_rate_id' => $taxRate->tax_rate_id,
        ]);

        $producto->setRelation('taxRate', $taxRate);

        $this->assertEquals(220.00, $producto->tax_amount);
        $this->assertEquals(1220.00, $producto->price_with_tax);
        $this->assertEquals(22.00, $producto->effective_tax_rate);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function producto_no_imponible_no_calcula_iva()
    {
        $producto = new Product([
            'price' => 1000.00,
            'is_taxable' => false,
            'tax_rate_percentage' => 15.00,
        ]);

        $this->assertEquals(0.00, $producto->tax_amount);
        $this->assertEquals(1000.00, $producto->price_with_tax);
        $this->assertEquals(0.00, $producto->effective_tax_rate);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function producto_sin_tasa_usa_tasa_cero()
    {
        $producto = new Product([
            'price' => 1000.00,
            'is_taxable' => true,
        ]);

        $this->assertEquals(0.00, $producto->tax_amount);
        $this->assertEquals(1000.00, $producto->price_with_tax);
        $this->assertEquals(0.00, $producto->effective_tax_rate);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_obtener_tasas_disponibles_por_pais()
    {
        $producto = new Product([
            'tax_country_code' => 'EC',
        ]);

        $tasas = $producto->getAvailableTaxRates();
        
        $this->assertIsArray($tasas);
        $this->assertCount(3, $tasas);
        $this->assertEquals('IVA 0%', $tasas[0]['name']);
        $this->assertEquals(0.00, $tasas[0]['rate']);
        $this->assertEquals('IVA 15%', $tasas[1]['name']);
        $this->assertEquals(15.00, $tasas[1]['rate']);
        $this->assertEquals('IVA 22%', $tasas[2]['name']);
        $this->assertEquals(22.00, $tasas[2]['rate']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function puede_verificar_categorias_imponibles()
    {
        $productoBienes = new Product(['tax_category' => 'goods']);
        $productoServicios = new Product(['tax_category' => 'services']);
        $productoTransporte = new Product(['tax_category' => 'transport']);
        $productoTransportePublico = new Product(['tax_category' => 'transport_public']);
        $productoSinCategoria = new Product(['tax_category' => null]);

        $this->assertTrue($productoBienes->isCategoryTaxable());
        $this->assertTrue($productoServicios->isCategoryTaxable());
        $this->assertFalse($productoTransporte->isCategoryTaxable());
        $this->assertFalse($productoTransportePublico->isCategoryTaxable());
        $this->assertTrue($productoSinCategoria->isCategoryTaxable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function prioriza_tasa_especifica_sobre_tasa_del_modelo()
    {
        $taxRate = TaxRate::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'IVA 22% - Especial',
            'rate' => 22.00,
            'description' => 'Tasa especial de IVA',
            'country_code' => 'EC',
            'is_active' => true,
        ]);

        $producto = new Product([
            'price' => 1000.00,
            'is_taxable' => true,
            'tax_rate_percentage' => 15.00, // Tasa específica
            'tax_rate_id' => $taxRate->tax_rate_id, // Tasa del modelo
        ]);

        $producto->setRelation('taxRate', $taxRate);

        // Debe usar la tasa específica (15%) en lugar de la del modelo (22%)
        $this->assertEquals(15.00, $producto->effective_tax_rate);
        $this->assertEquals(150.00, $producto->tax_amount);
        $this->assertEquals(1150.00, $producto->price_with_tax);
    }
}
