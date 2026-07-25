<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Category;

use App\Models\Category;
use App\Models\Restaurant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    private function categoryPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Bebidas',
            'description' => 'Todas as bebidas do cardápio',
            'sort_order' => 1,
        ], $overrides);
    }

    // ─── List ────────────────────────────────────────────────────

    public function test_owner_can_list_categories(): void
    {
        Category::factory()->count(3)->create(['restaurant_id' => $this->restaurant->id]);

        $otherRestaurant = Restaurant::factory()->create();
        Category::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->getJson("/api/v1/restaurants/{$this->restaurant->id}/categories", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['*' => ['id', 'name', 'slug']]]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_unauthenticated_user_cannot_list_categories(): void
    {
        $response = $this->getJson("/api/v1/restaurants/{$this->restaurant->id}/categories");

        $response->assertStatus(401);
    }

    // ─── Create ──────────────────────────────────────────────────

    public function test_owner_can_create_a_category(): void
    {
        $payload = $this->categoryPayload();

        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/categories",
            $payload,
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Bebidas')
            ->assertJsonPath('message', 'Category created successfully.');

        $this->assertDatabaseHas('categories', [
            'name' => 'Bebidas',
            'restaurant_id' => $this->restaurant->id,
        ]);
    }

    public function test_owner_cannot_create_category_without_name(): void
    {
        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/categories",
            [],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_user_cannot_create_category(): void
    {
        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/categories",
            $this->categoryPayload(),
        );

        $response->assertStatus(401);
    }

    // ─── Show ────────────────────────────────────────────────────

    public function test_owner_can_show_a_category(): void
    {
        $category = Category::factory()->create(['restaurant_id' => $this->restaurant->id]);

        $response = $this->getJson(
            "/api/v1/restaurants/{$this->restaurant->id}/categories/{$category->id}",
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', $category->name);
    }

    public function test_owner_cannot_show_category_from_another_restaurant(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->getJson(
            "/api/v1/restaurants/{$this->restaurant->id}/categories/{$category->id}",
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(404);
    }

    // ─── Update ──────────────────────────────────────────────────

    public function test_owner_can_update_a_category(): void
    {
        $category = Category::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Nome Antigo',
        ]);

        $response = $this->putJson(
            "/api/v1/restaurants/{$this->restaurant->id}/categories/{$category->id}",
            ['name' => 'Nome Novo'],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nome Novo')
            ->assertJsonPath('message', 'Category updated successfully.');
    }

    public function test_owner_can_partially_update_a_category(): void
    {
        $category = Category::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Nome Original',
            'description' => 'Descrição antiga',
        ]);

        $response = $this->putJson(
            "/api/v1/restaurants/{$this->restaurant->id}/categories/{$category->id}",
            ['description' => 'Nova descrição'],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nome Original')
            ->assertJsonPath('data.description', 'Nova descrição');
    }

    public function test_owner_cannot_update_category_from_another_restaurant(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->putJson(
            "/api/v1/restaurants/{$this->restaurant->id}/categories/{$category->id}",
            ['name' => 'Hacker'],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(404);
    }

    // ─── Delete ──────────────────────────────────────────────────

    public function test_owner_can_delete_a_category(): void
    {
        $category = Category::factory()->create(['restaurant_id' => $this->restaurant->id]);

        $response = $this->deleteJson(
            "/api/v1/restaurants/{$this->restaurant->id}/categories/{$category->id}",
            [],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Category deleted successfully.');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_owner_cannot_delete_category_from_another_restaurant(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->deleteJson(
            "/api/v1/restaurants/{$this->restaurant->id}/categories/{$category->id}",
            [],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_delete_category(): void
    {
        $category = Category::factory()->create(['restaurant_id' => $this->restaurant->id]);

        $response = $this->deleteJson(
            "/api/v1/restaurants/{$this->restaurant->id}/categories/{$category->id}",
        );

        $response->assertStatus(401);
    }
}
