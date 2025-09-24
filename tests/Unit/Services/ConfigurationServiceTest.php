<?php

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ConfigurationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $configService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test tenant with all required fields
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'legal_id' => '123456789',
            'email' => 'test@example.com',
            'address' => '123 Test St',
            'phone' => '1234567890',
            'website' => 'https://test.com',
            'industry' => 'Technology',
            'is_active' => true,
            'subscription_plan' => 'basic',
            'subscription_ends_at' => now()->addYear(),
            'settings' => [],
            'logo' => 'logo.png',
            'slogan' => 'Test Slogan',
        ]);
        
        // Set the current tenant in the container
        app()->instance('currentTenant', $this->tenant);
        
        // Initialize configuration service for the tenant
        $this->configService = app(ConfigurationService::class);
    }

    /** @test */
    public function it_can_initialize_core_settings()
    {
        // Clear cache first
        Cache::flush();
        
        // Initialize core settings
        $this->configService->initializeCoreSettings($this->tenant->id);
        
        // Check if core settings are set
        $this->assertEquals('Test Tenant', $this->configService->get('name', null, 'company'));
        $this->assertEquals('123456789', $this->configService->get('legal_id', null, 'company'));
        $this->assertEquals('0.00', $this->configService->get('sales_tax_rate', null, 'tax'));
    }

    /** @test */
    public function it_can_get_and_set_settings()
    {
        // Set a setting
        $this->configService->set('test_key', 'test_value', 'test_group');
        
        // Get the setting
        $value = $this->configService->get('test_key', null, 'test_group');
        
        $this->assertEquals('test_value', $value);
    }

    /** @test */
    public function it_returns_default_value_for_nonexistent_setting()
    {
        $value = $this->configService->get('nonexistent_key', 'default_value', 'test_group');
        $this->assertEquals('default_value', $value);
    }

    /** @test */
    public function it_can_set_multiple_settings_at_once()
    {
        $settings = [
            'setting1' => 'value1',
            'setting2' => 'value2',
        ];
        
        $this->configService->setMany($settings, 'test_group');
        
        $this->assertEquals('value1', $this->configService->get('setting1', null, 'test_group'));
        $this->assertEquals('value2', $this->configService->get('setting2', null, 'test_group'));
    }

    /** @test */
    public function it_can_get_all_settings_for_a_tenant()
    {
        // Add some test settings
        $this->configService->set('setting1', 'value1', 'group1');
        $this->configService->set('setting2', 'value2', 'group2');
        
        // Get all settings
        $allSettings = $this->configService->all();
        
        $this->assertArrayHasKey('group1', $allSettings);
        $this->assertArrayHasKey('setting1', $allSettings['group1']);
        $this->assertEquals('value1', $allSettings['group1']['setting1']);
    }

    /** @test */
    public function it_handles_tenant_configuration_through_model()
    {
        // Make sure we have a tenant ID
        $this->assertNotNull($this->tenant->id);
        
        // Test that the tenant model can access the config service
        $this->tenant->setConfig('test_key', 'test_value', 'test_group');
        
        $value = $this->tenant->getConfig('test_key', null, 'test_group');
        $this->assertEquals('test_value', $value);
    }

    /** @test */
    public function it_synchronizes_tenant_attributes_with_configuration()
    {
        // Initialize configuration
        $this->configService->initializeCoreSettings($this->tenant->id);
        
        // Update tenant configuration
        $this->tenant->update([
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
        
        // Sync settings
        $this->tenant->syncSettingsWithTenant();
        
        // Refresh the config service to ensure we get fresh data
        $configService = app(ConfigurationService::class);
        
        // Check if settings were updated
        $this->assertEquals('Updated Name', $configService->get('name', null, 'company'));
        $this->assertEquals('updated@example.com', $configService->get('email', null, 'company'));
    }
}
