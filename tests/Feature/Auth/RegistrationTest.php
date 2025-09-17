<?php

namespace Tests\Feature\Auth;

use App\Models\CrmUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

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
        Event::fake();
        $tenant = Tenant::factory()->create();
        
        // Generate test data
        $email = 'test_' . time() . '@example.com';
        $userData = [
            'username' => 'testuser_' . time(),
            'full_name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
            'tenant_id' => $tenant->id,
            '_token' => csrf_token(),
        ];
        
        // Make the registration request
        $response = $this->withSession(['_token' => $userData['_token']])
            ->post('/register', $userData);
        
        // Check the database directly
        $user = CrmUser::where('email', $email)->first();
        $this->assertNotNull($user, 'User was not created in the database');
        
        // Verify the response
        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard'));
        
        // Check if user is authenticated
        $this->assertAuthenticated('crm');
    }

    public function test_email_verification_after_registration(): void
    {
        $tenant = Tenant::factory()->create();
        $user = CrmUser::factory()->unverified()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => null,
        ]);
        
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
        );
        
        $response = $this->actingAs($user, 'crm')
            ->get($verificationUrl);
            
        $response->assertRedirect(route('dashboard') . '?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_tenant_switching_after_registration(): void
    {
        $tenant = Tenant::factory()->create();
        $user = CrmUser::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        
        // Attach the user to the tenant with the necessary permissions
        $user->tenants()->attach($tenant->id, ['is_primary' => true]);
        
        $response = $this->actingAs($user, 'crm')
            ->withSession(['_token' => csrf_token()])
            ->post(route('tenants.switch', $tenant), [
                '_token' => csrf_token(),
                '_method' => 'POST',
            ]);
            
        // Debug output
        echo "\nResponse status: " . $response->status();
        echo "\nRedirect target: " . ($response->headers->get('Location') ?? 'No redirect');
        echo "\nSession data: " . json_encode(session()->all());
        
        $response->assertStatus(302);
        
        // Check if redirected to either dashboard or home
        $response->assertValid();
        $this->assertContains(
            $response->headers->get('Location'), 
            [
                url(route('dashboard')), 
                url('/')
            ]
        );
        
        $this->assertEquals($tenant->id, session('current_tenant_id'));
    }

    public function test_invalid_registration_attempts(): void
    {
        // Test missing required fields
        $response = $this->post('/register', []);
        $response->assertSessionHasErrors(['full_name', 'email', 'password', 'tenant_id']);
        
        // Test invalid email
        $response = $this->post('/register', ['email' => 'invalid-email']);
        $response->assertSessionHasErrors('email');
        
        // Test password confirmation
        $response = $this->post('/register', [
            'password' => 'password',
            'password_confirmation' => 'wrong'
        ]);
        $response->assertSessionHasErrors('password');
    }

    public function test_login_with_newly_registered_user()
    {
        $tenant = Tenant::factory()->create();
        $password = 'password';
        $user = CrmUser::factory()->create([
            'tenant_id' => $tenant->id,
            'password' => Hash::make($password),
        ]);
        
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => $password,
            '_token' => csrf_token(),
        ]);
        
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user, 'crm');
    }

    public function test_logout_functionality()
    {
        $user = CrmUser::factory()->create();
        
        $response = $this->actingAs($user, 'crm')
            ->post('/logout');
            
        $response->assertRedirect('/');
        $this->assertGuest('crm');
    }

    protected function getValidUserData($tenantId, array $overrides = []): array
    {
        return array_merge([
            'username' => 'testuser_' . Str::random(10),
            'full_name' => 'Test User',
            'email' => 'test_' . Str::random(10) . '@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'tenant_id' => $tenantId,
            '_token' => csrf_token(),
        ], $overrides);
    }
}