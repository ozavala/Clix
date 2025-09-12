<?php

namespace Tests\Feature;

use App\Models\CrmUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LandingLoginTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test que verifica que un usuario no autenticado puede ver la página de landing
     */
    public function test_unauthenticated_user_can_see_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('landing');
        $response->assertSee('Welcome to');
        $response->assertSee('CLiX');
        $response->assertSee('Sign In');
    }

    /**
     * Test que verifica que un usuario autenticado puede acceder a la landing (sin redirección)
     */
    public function test_authenticated_user_can_access_landing(): void
    {
        $user = CrmUser::factory()->create([
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('landing');
    }

    /**
     * Test que verifica el login exitoso desde la página de landing
     */
    public function test_user_can_login_successfully_from_landing_page(): void
    {
        $response = $this->get('/');
        $token = $this->getTokenFromResponse($response);

        $email = $this->faker->unique()->safeEmail;
        $user = CrmUser::factory()->create([
            'email' => $email,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            '_token' => $token,
            'email' => $email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Test que verifica que un usuario no verificado no puede acceder al dashboard
     */
    public function test_unverified_user_cannot_access_dashboard(): void
    {
        $user = CrmUser::factory()->create([
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/verify-email');
    }

    /**
     * Test que verifica que un usuario verificado puede acceder al dashboard
     */
    public function test_verified_user_can_access_dashboard(): void
    {
        $user = CrmUser::factory()->create([
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertAuthenticated();
    }

    /**
     * Test que verifica que un usuario no verificado puede acceder al dashboard en desarrollo
     */
    public function test_unverified_user_can_access_dashboard_in_development(): void
    {
        $user = CrmUser::factory()->create([
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'email_verified_at' => null, // Usuario no verificado
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertAuthenticated();
    }

    /**
     * Test que verifica el login fallido con credenciales incorrectas
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->get('/');
        $token = $this->getTokenFromResponse($response);

        $email = $this->faker->unique()->safeEmail;
        $user = CrmUser::factory()->create([
            'email' => $email,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            '_token' => $token,
            'email' => $email,
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Test que verifica que el formulario de login en la landing tiene los campos correctos
     */
    public function test_landing_page_has_correct_login_form(): void
    {
        $response = $this->get('/');
        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]+name=["\]email["\']/i', $html);
        $this->assertMatchesRegularExpression('/<input[^>]+name=["\]password["\']/i', $html);
        $this->assertMatchesRegularExpression('/<input[^>]+name=["\]remember["\']/i', $html);
        $this->assertMatchesRegularExpression('/<form[^>]+action=["\'][^"\\]*login["\']/i', $html);
        $this->assertMatchesRegularExpression('/<form[^>]+method=["\']post["\']/i', $html);
    }

    /**
     * Test que verifica el flujo completo de login desde landing hasta dashboard
     */
    public function test_complete_login_flow_from_landing_to_dashboard(): void
    {
        $response = $this->get('/');
        $token = $this->getTokenFromResponse($response);

        $email = $this->faker->unique()->safeEmail;
        $user = CrmUser::factory()->create([
            'email' => $email,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // 1. Acceder a la landing page
        $landingResponse = $this->get('/');
        $landingResponse->assertStatus(200);

        // 2. Hacer login
        $loginResponse = $this->post('/login', [
            '_token' => $token,
            'email' => $email,
            'password' => 'password',
        ]);

        $loginResponse->assertRedirect('/dashboard');

        // 3. Verificar que está autenticado
        $this->assertAuthenticated();
    }

    /**
     * Test que verifica que el remember me funciona correctamente
     */
    public function test_remember_me_functionality(): void
    {
        $response = $this->get('/');
        $token = $this->getTokenFromResponse($response);

        $email = $this->faker->unique()->safeEmail;
        $user = CrmUser::factory()->create([
            'email' => $email,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            '_token' => $token,
            'email' => $email,
            'password' => 'password',
            'remember' => 'on',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        
        // Verificar que el remember token se guardó
        $this->assertNotNull($user->fresh()->remember_token);
    }

    private function getTokenFromResponse($response)
    {
        $content = $response->getContent();
        preg_match('/<input type="hidden" name="_token" value="(.*)">/', $content, $matches);
        return $matches[1] ?? null;
    }
}
