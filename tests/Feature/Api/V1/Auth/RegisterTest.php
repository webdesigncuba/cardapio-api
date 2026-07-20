<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use App\Models\Restaurant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string API_REGISTER = '/api/v1/auth/register';

    public function test_owner_can_register_with_restaurant(): void
    {
        $payload = [
            'name' => 'María García',
            'email' => 'maria@emporio.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'restaurant_name' => 'Emporio de María',
        ];

        $response = $this->postJson(self::API_REGISTER, $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'restaurant' => ['id', 'name', 'slug'],
                    'tenant' => ['id', 'name'],
                    'token',
                ],
                'message',
            ])
            ->assertJsonPath('data.user.name', 'María García')
            ->assertJsonPath('data.user.email', 'maria@emporio.com')
            ->assertJsonPath('data.user.role', 'owner')
            ->assertJsonPath('data.restaurant.name', 'Emporio de María')
            ->assertJsonPath('data.tenant.name', 'Emporio de María');

        // Verify the user is authenticated with the returned token
        $token = $response->json('data.token');
        $this->assertNotNull($token);

        // Assert records exist in database
        $this->assertEquals(1, User::count());
        $this->assertEquals(1, Tenant::count());
        $this->assertEquals(1, Restaurant::count());

        $user = User::first();
        $this->assertNull(auth()->user()); // API es stateless, no inicia sesión
        $this->assertEquals('maria@emporio.com', $user->email);
    }

    public function test_register_requires_valid_email(): void
    {
        $payload = [
            'name' => 'María García',
            'email' => 'email-invalido',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'restaurant_name' => 'Emporio de María',
        ];

        $response = $this->postJson(self::API_REGISTER, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'maria@emporio.com']);

        $payload = [
            'name' => 'Otra María',
            'email' => 'maria@emporio.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'restaurant_name' => 'Otro Negocio',
        ];

        $response = $this->postJson(self::API_REGISTER, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $payload = [
            'name' => 'María García',
            'email' => 'maria@emporio.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'DifferentPass123!',
            'restaurant_name' => 'Emporio de María',
        ];

        $response = $this->postJson(self::API_REGISTER, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_requires_restaurant_name(): void
    {
        $payload = [
            'name' => 'María García',
            'email' => 'maria@emporio.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ];

        $response = $this->postJson(self::API_REGISTER, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['restaurant_name']);
    }

    public function test_register_returns_422_when_required_fields_missing(): void
    {
        $response = $this->postJson(self::API_REGISTER, []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'restaurant_name']);
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $payload = [
            'name' => 'María García',
            'email' => 'maria@emporio.com',
            'password' => 'Short1!',
            'password_confirmation' => 'Short1!',
            'restaurant_name' => 'Emporio de María',
        ];

        $response = $this->postJson(self::API_REGISTER, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
