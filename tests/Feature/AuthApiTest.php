<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'John Kamau',
            'email'                 => 'john@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'monthly_budget'        => 50000,
            'currency'              => 'KES',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => ['user', 'token', 'type'],
                 ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/auth/register', [
            'name'                  => 'Jane',
            'email'                 => 'taken@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(422)->assertJsonPath('errors.email.0', 'The email has already been taken.');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['data' => ['token', 'expires_in']]);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct')]);

        $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'wrong',
        ])->assertStatus(401)->assertJsonPath('message', 'Invalid credentials');
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user    = User::factory()->create();
        $token   = JWTAuth::fromUser($user);
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/api/auth/me', $headers)
             ->assertOk()
             ->assertJsonPath('data.email', $user->email);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
        $this->getJson('/api/tasks')->assertStatus(401);
        $this->getJson('/api/dashboard')->assertStatus(401);
    }

    public function test_user_can_update_budget_settings(): void
    {
        $user    = User::factory()->create(['monthly_budget' => 30000]);
        $token   = JWTAuth::fromUser($user);
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->putJson('/api/auth/profile', ['monthly_budget' => 60000], $headers)
             ->assertOk()
             ->assertJsonPath('data.monthly_budget', '60000.00');
    }

    public function test_dashboard_returns_budget_from_profile(): void
    {
        $user    = User::factory()->create(['monthly_budget' => 45000, 'currency' => 'KES']);
        $token   = JWTAuth::fromUser($user);
        $headers = ['Authorization' => "Bearer {$token}"];

        $response = $this->getJson('/api/dashboard', $headers)
             ->assertOk();

        $this->assertEquals(45000.0, (float) $response->json('data.finance.current_month.budget'));
        $this->assertEquals(45000, $response->json('data.user.monthly_budget'));
    }

    public function test_web_register_redirects_to_login(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Glow User',
            'email'                 => 'glow@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'monthly_budget'        => 45000,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['email' => 'glow@example.com', 'monthly_budget' => 45000]);
    }
}
