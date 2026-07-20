<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const string API_LOGOUT = '/api/v1/auth/logout';

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();

        $this->user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->token = $this->user->createToken('auth-token')->plainTextToken;
    }

    public function test_authenticated_user_can_logout(): void
    {
        $response = $this->postJson(self::API_LOGOUT, [], [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Logged out successfully.');

        // Token should be revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_token_is_invalid_after_logout(): void
    {
        $tokenId = explode('|', $this->token)[0];

        $this->postJson(self::API_LOGOUT, [], [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        // Assert the token was removed from the database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);

        // Assert the user has no tokens left
        $this->assertEquals(0, $this->user->tokens()->count());
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson(self::API_LOGOUT);

        $response->assertStatus(401);
    }

    public function test_logout_with_invalid_token_returns_401(): void
    {
        $response = $this->postJson(self::API_LOGOUT, [], [
            'Authorization' => 'Bearer invalid-token-123',
        ]);

        $response->assertStatus(401);
    }

    public function test_logout_only_revokes_current_user_tokens(): void
    {
        // Create a second user with their own token
        $tenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $otherToken = $otherUser->createToken('auth-token')->plainTextToken;

        // Logout the first user
        $this->postJson(self::API_LOGOUT, [], [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        // First user's token should be revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
        ]);

        // Second user's token should still exist
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $otherUser->id,
        ]);
    }
}
