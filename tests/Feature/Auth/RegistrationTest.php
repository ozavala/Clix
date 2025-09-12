<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Auth::shouldUse('web');
        $this->flushSession();
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();
        
        $response = $this->post('/register', [
            'username' => 'testuser',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'tenant_id' => $tenant->id,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        
        $this->assertDatabaseHas('crm_users', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'full_name' => 'Test User',
        ]);
    }
}
