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
        Auth::shouldUse('crm');
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
        // Fetch the registration page to get CSRF token
        $getResponse = $this->get('/register');
        $content = $getResponse->getContent();
        preg_match('/<input type="hidden" name="_token" value="([^"]+)">/', $content, $matches);
        $token = $matches[1] ?? null;

        $uniqueEmail = 'test_' . uniqid() . '@example.com';

        $response = $this->post('/register', [
            '_token' => $token,
            'username' => 'testuser',
            'full_name' => 'Test User',
            'email' => $uniqueEmail,
            'password' => 'password',
            'password_confirmation' => 'password',
            'tenant_id' => $tenant->id,
        ]);

        $this->assertAuthenticated('crm');
        $response->assertRedirect(route('dashboard', absolute: false));
        
        $this->assertDatabaseHas('crm_users', [
            'username' => 'testuser',
            'email' => $uniqueEmail,
            'full_name' => 'Test User',
        ]);
    }
}
