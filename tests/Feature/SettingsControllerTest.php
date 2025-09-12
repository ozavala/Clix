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
        // Create a core setting for the test
        $setting = Setting::create([
            'key' => 'test_setting',
            'value' => 'Old Value',
            'type' => 'core',
            'is_editable' => true,
            'tenant_id' => $this->tenant->id
        ]);
        
        $response = $this->patch(route('settings.update'), [
            'test_setting' => 'New Value',
        ]);
        
        $response->assertRedirect(route('settings.edit'));
        
        // Refresh the setting from the database
        $setting->refresh();
        $this->assertEquals('New Value', $setting->value);
    }

    public function test_create_and_delete_custom_setting()
    {
        $customKey = 'custom_field_' . time(); // Ensure unique key
        
        // Create custom setting
        $response = $this->post(route('settings.custom.store'), [
            'key' => $customKey,
            'value' => 'Test Value',
        ]);
        
        $response->assertRedirect(route('settings.edit'));
        
        // Get the specific setting we just created
        $setting = Setting::where('key', $customKey)->first();
        
        $this->assertNotNull($setting, 'Custom setting was not created');
        $this->assertEquals('Test Value', $setting->value);
        $this->assertEquals('custom', $setting->type);
        
        // Delete custom setting
        $response = $this->delete(route('settings.custom.destroy', $setting));
        $response->assertRedirect(route('settings.edit'));
        
        // Verify the setting was deleted
        $this->assertNull(
            Setting::where('key', $customKey)->first()
        );
    }

    public function test_cannot_delete_core_setting()
    {
        $core = Setting::where('key', 'name')->first();
        $response = $this->delete(route('settings.custom.destroy', $core));
        $response->assertRedirect(route('settings.edit'));
        $this->assertDatabaseHas('settings', ['key' => 'name']);
    }
} 