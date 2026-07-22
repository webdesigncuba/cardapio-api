<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Client;

use App\Models\Client;
use App\Models\Restaurant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    private string $token;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->token = $this->user->createToken('auth-token')->plainTextToken;
        $this->restaurant = Restaurant::factory()->create(['tenant_id' => $tenant->id]);
    }

    private function clientPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'João Silva',
            'address' => 'Rua das Flores',
            'number' => '123',
            'cep' => '12345-678',
            'complement' => 'Apto 42',
            'uf' => 'SP',
            'bario' => 'Centro',
        ], $overrides);
    }

    // ─── List ────────────────────────────────────────────────────

    public function test_owner_can_list_clients(): void
    {
        Client::factory()->count(3)->create(['restaurant_id' => $this->restaurant->id]);

        // Client from another restaurant
        $otherRestaurant = Restaurant::factory()->create();
        Client::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->getJson("/api/v1/restaurants/{$this->restaurant->id}/clients", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['*' => ['id', 'name', 'address']]]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_unauthenticated_user_cannot_list_clients(): void
    {
        $response = $this->getJson("/api/v1/restaurants/{$this->restaurant->id}/clients");

        $response->assertStatus(401);
    }

    // ─── Create ──────────────────────────────────────────────────

    public function test_owner_can_create_a_client(): void
    {
        $payload = $this->clientPayload();

        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/clients",
            $payload,
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'João Silva')
            ->assertJsonPath('message', 'Client created successfully.');

        $this->assertDatabaseHas('clients', [
            'name' => 'João Silva',
            'restaurant_id' => $this->restaurant->id,
        ]);
    }

    public function test_owner_cannot_create_client_without_required_fields(): void
    {
        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/clients",
            [],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'address', 'number', 'cep', 'uf', 'bario']);
    }

    public function test_unauthenticated_user_cannot_create_client(): void
    {
        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/clients",
            $this->clientPayload(),
        );

        $response->assertStatus(401);
    }

    // ─── Show ────────────────────────────────────────────────────

    public function test_owner_can_show_a_client(): void
    {
        $client = Client::factory()->create(['restaurant_id' => $this->restaurant->id]);

        $response = $this->getJson(
            "/api/v1/restaurants/{$this->restaurant->id}/clients/{$client->id}",
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('data.name', $client->name);
    }

    public function test_owner_cannot_show_client_from_another_restaurant(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $client = Client::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->getJson(
            "/api/v1/restaurants/{$this->restaurant->id}/clients/{$client->id}",
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(404);
    }

    // ─── Update ──────────────────────────────────────────────────

    public function test_owner_can_update_a_client(): void
    {
        $client = Client::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Nome Antigo',
        ]);

        $response = $this->putJson(
            "/api/v1/restaurants/{$this->restaurant->id}/clients/{$client->id}",
            ['name' => 'Nome Novo'],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nome Novo')
            ->assertJsonPath('message', 'Client updated successfully.');
    }

    public function test_owner_can_partially_update_a_client(): void
    {
        $client = Client::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Nome Original',
            'address' => 'Rua Antiga, 123',
        ]);

        $response = $this->putJson(
            "/api/v1/restaurants/{$this->restaurant->id}/clients/{$client->id}",
            ['address' => 'Nova Rua, 456'],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nome Original')
            ->assertJsonPath('data.address', 'Nova Rua, 456');
    }

    public function test_owner_cannot_update_client_from_another_restaurant(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $client = Client::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->putJson(
            "/api/v1/restaurants/{$this->restaurant->id}/clients/{$client->id}",
            ['name' => 'Hacker'],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(404);
    }

    // ─── Delete ──────────────────────────────────────────────────

    public function test_owner_can_delete_a_client(): void
    {
        $client = Client::factory()->create(['restaurant_id' => $this->restaurant->id]);

        $response = $this->deleteJson(
            "/api/v1/restaurants/{$this->restaurant->id}/clients/{$client->id}",
            [],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Client deleted successfully.');

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_owner_cannot_delete_client_from_another_restaurant(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $client = Client::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->deleteJson(
            "/api/v1/restaurants/{$this->restaurant->id}/clients/{$client->id}",
            [],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_delete_client(): void
    {
        $client = Client::factory()->create(['restaurant_id' => $this->restaurant->id]);

        $response = $this->deleteJson(
            "/api/v1/restaurants/{$this->restaurant->id}/clients/{$client->id}",
        );

        $response->assertStatus(401);
    }
}
