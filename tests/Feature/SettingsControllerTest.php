<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\CrmUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a tenant
        $this->tenant = \App\Models\Tenant::factory()->create();
        
        // Create a user for the tenant
        $this->user = \App\Models\CrmUser::factory()->forTenant($this->tenant)->create();
        
        // Seed settings for the tenant
        $this->seed(\Database\Seeders\SettingsTableSeeder::class);
        
        // Set the tenant for the test
        $this->actingAs($this->user, 'crm');
        
        // Assign permissions needed to create and edit settings
        // This is required by the authorization logic (Gate 'edit-settings')
        $this->givePermission($this->user, ['edit-settings', 'create-settings']);
    }

    public function test_edit_core_settings()
    {
        // Ensure the setting exists for the tenant
        Setting::create([
            'key' => 'company_name',
            'value' => 'Old Company Name',
            'type' => 'core',
            'tenant_id' => $this->tenant->id
        ]);
        
        $response = $this->patch(route('settings.update'), [
            'company_name' => 'New Company Name',
        ]);
        
        $response->assertRedirect(route('settings.edit'));
        $this->assertEquals('New Company Name', 
            Setting::where('key', 'company_name')
                ->where('tenant_id', $this->tenant->id)
                ->first()->value
        );
    }

    public function test_create_and_delete_custom_setting()
    {
        // Create custom setting
        $response = $this->post(route('settings.custom.store'), [
            'key' => 'custom_field',
            'value' => 'Valor',
        ]);
        
        $response->assertRedirect(route('settings.edit'));
        
        // Verify the setting was created with the correct tenant_id
        $this->assertDatabaseHas('settings', [
            'key' => 'custom_field', 
            'type' => 'custom',
            'tenant_id' => $this->tenant->id
        ]);
        
        // Get the setting with tenant scope
        $setting = Setting::where('key', 'custom_field')
            ->where('tenant_id', $this->tenant->id)
            ->first();
            
        // Delete custom setting
        $response = $this->delete(route('settings.custom.destroy', $setting));
        $response->assertRedirect(route('settings.edit'));
        
        // Verify the setting was deleted
        $this->assertDatabaseMissing('settings', [
            'key' => 'custom_field',
            'tenant_id' => $this->tenant->id
        ]);
    }

    public function test_cannot_delete_core_setting()
    {
        $core = Setting::where('key', 'company_name')->first();
        $response = $this->delete(route('settings.custom.destroy', $core));
        $response->assertRedirect(route('settings.edit'));
        $this->assertDatabaseHas('settings', ['key' => 'company_name']);
    }
} 