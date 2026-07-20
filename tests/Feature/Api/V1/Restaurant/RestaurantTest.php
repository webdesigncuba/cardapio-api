<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Restaurant;

use App\Models\Restaurant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RestaurantTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->token = $this->user->createToken('auth-token')->plainTextToken;
    }

    public function test_owner_can_list_their_restaurants(): void
    {
        Restaurant::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create a restaurant from another tenant
        $otherTenant = Tenant::factory()->create();
        Restaurant::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->getJson('/api/v1/restaurants', [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'phone', 'email', 'address', 'is_active'],
                ],
            ]);

        // Only 3 restaurants from the user's tenant, not the 4th one
        $this->assertCount(3, $response->json('data'));
    }

    public function test_unauthenticated_user_cannot_list_restaurants(): void
    {
        $response = $this->getJson('/api/v1/restaurants');

        $response->assertStatus(401);
    }

    public function test_owner_can_create_a_restaurant(): void
    {
        $payload = [
            'name' => 'La Casa de las Empanadas',
        ];

        $response = $this->postJson('/api/v1/restaurants', $payload, [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug', 'phone', 'email', 'address', 'is_active'],
                'message',
            ])
            ->assertJsonPath('data.name', 'La Casa de las Empanadas')
            ->assertJsonPath('data.slug', 'la-casa-de-las-empanadas')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('message', 'Restaurant created successfully.');

        $this->assertDatabaseHas('restaurants', [
            'name' => 'La Casa de las Empanadas',
            'slug' => 'la-casa-de-las-empanadas',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_owner_can_create_a_restaurant_with_optional_fields(): void
    {
        $payload = [
            'name' => 'Emporio de María',
            'phone' => '+584141234567',
            'email' => 'emporio@correo.com',
            'address' => 'Calle Principal, Local 5',
        ];

        $response = $this->postJson('/api/v1/restaurants', $payload, [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.phone', '+584141234567')
            ->assertJsonPath('data.email', 'emporio@correo.com')
            ->assertJsonPath('data.address', 'Calle Principal, Local 5');
    }

    public function test_owner_cannot_create_restaurant_without_name(): void
    {
        $response = $this->postJson('/api/v1/restaurants', [], [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_user_cannot_create_restaurant(): void
    {
        $response = $this->postJson('/api/v1/restaurants', [
            'name' => 'Mi Restaurante',
        ]);

        $response->assertStatus(401);
    }

    public function test_owner_can_show_a_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->getJson("/api/v1/restaurants/{$restaurant->id}", [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug', 'tenant_id'],
            ])
            ->assertJsonPath('data.id', $restaurant->id);
    }

    public function test_owner_can_update_a_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Nombre Original',
        ]);

        $response = $this->putJson("/api/v1/restaurants/{$restaurant->id}", [
            'name' => 'Nombre Actualizado',
        ], [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nombre Actualizado')
            ->assertJsonPath('data.slug', 'nombre-actualizado')
            ->assertJsonPath('message', 'Restaurant updated successfully.');
    }

    public function test_owner_can_partially_update_a_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Nombre Original',
            'phone' => null,
        ]);

        $response = $this->putJson("/api/v1/restaurants/{$restaurant->id}", [
            'phone' => '+584141234567',
        ], [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nombre Original') // Unchanged
            ->assertJsonPath('data.phone', '+584141234567'); // Updated
    }

    public function test_owner_can_delete_a_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->deleteJson("/api/v1/restaurants/{$restaurant->id}", [], [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Restaurant deleted successfully.');

        $this->assertDatabaseMissing('restaurants', ['id' => $restaurant->id]);
    }

    public function test_unauthenticated_user_cannot_delete_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->deleteJson("/api/v1/restaurants/{$restaurant->id}");

        $response->assertStatus(401);
    }
}
