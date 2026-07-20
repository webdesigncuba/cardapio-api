<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string API_LOGIN = '/api/v1/auth/login';

    private const string PASSWORD = 'SecurePass123!';

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'maria@emporio.com',
            'password' => bcrypt(self::PASSWORD),
            'role' => 'owner',
        ]);
    }

    public function test_owner_can_login_with_valid_credentials(): void
    {
        $payload = [
            'email' => 'maria@emporio.com',
            'password' => self::PASSWORD,
        ];

        $response = $this->postJson(self::API_LOGIN, $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'role'],
                    'token',
                ],
            ])
            ->assertJsonPath('data.user.email', 'maria@emporio.com')
            ->assertJsonPath('data.user.role', 'owner');

        $token = $response->json('data.token');
        $this->assertNotNull($token);

        // Token should be a valid Sanctum token format
        $this->assertStringContainsString('|', $token);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $payload = [
            'email' => 'maria@emporio.com',
            'password' => 'WrongPassword123!',
        ];

        $response = $this->postJson(self::API_LOGIN, $payload);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $payload = [
            'email' => 'no-existe@correo.com',
            'password' => self::PASSWORD,
        ];

        $response = $this->postJson(self::API_LOGIN, $payload);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_login_requires_email(): void
    {
        $payload = [
            'password' => self::PASSWORD,
        ];

        $response = $this->postJson(self::API_LOGIN, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_password(): void
    {
        $payload = [
            'email' => 'maria@emporio.com',
        ];

        $response = $this->postJson(self::API_LOGIN, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_requires_valid_email_format(): void
    {
        $payload = [
            'email' => 'email-invalido',
            'password' => self::PASSWORD,
        ];

        $response = $this->postJson(self::API_LOGIN, $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_revokes_previous_tokens(): void
    {
        // First login
        $response = $this->postJson(self::API_LOGIN, [
            'email' => 'maria@emporio.com',
            'password' => self::PASSWORD,
        ]);

        $firstToken = $response->json('data.token');
        $firstTokenId = explode('|', $firstToken)[0];
        $this->assertNotNull($firstToken);

        // Second login
        $response = $this->postJson(self::API_LOGIN, [
            'email' => 'maria@emporio.com',
            'password' => self::PASSWORD,
        ]);

        $secondToken = $response->json('data.token');
        $this->assertNotNull($secondToken);
        $this->assertNotEquals($firstToken, $secondToken);

        // Assert the first token was deleted from the database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $firstTokenId,
        ]);
    }
}
