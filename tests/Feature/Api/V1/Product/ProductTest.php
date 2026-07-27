<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    private string $token;

    private Restaurant $restaurant;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->token = $this->user->createToken('auth-token')->plainTextToken;
        $this->restaurant = Restaurant::factory()->create(['tenant_id' => $tenant->id]);
        $this->category = Category::factory()->create(['restaurant_id' => $this->restaurant->id]);
    }

    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => $this->category->id,
            'name' => 'X-Burger',
            'description' => 'Hambúrguer artesanal com queijo',
            'price' => 29.90,
            'estimated_minutes' => 15,
            'sort_order' => 1,
        ], $overrides);
    }

    // ─── List ────────────────────────────────────────────────────

    public function test_owner_can_list_products(): void
    {
        Product::factory()->count(3)->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
        ]);

        $otherRestaurant = Restaurant::factory()->create();
        Product::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->getJson("/api/v1/restaurants/{$this->restaurant->id}/products", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['*' => ['id', 'name', 'price', 'category']]]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_unauthenticated_user_cannot_list_products(): void
    {
        $response = $this->getJson("/api/v1/restaurants/{$this->restaurant->id}/products");

        $response->assertStatus(401);
    }

    // ─── Create ──────────────────────────────────────────────────

    public function test_owner_can_create_a_product(): void
    {
        $payload = $this->productPayload();

        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/products",
            $payload,
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'X-Burger')
            ->assertJsonPath('message', 'Product created successfully.');

        $this->assertDatabaseHas('products', [
            'name' => 'X-Burger',
            'restaurant_id' => $this->restaurant->id,
        ]);
    }

    public function test_owner_cannot_create_product_without_required_fields(): void
    {
        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/products",
            [],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'name', 'price']);
    }

    public function test_owner_cannot_create_product_with_invalid_category(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $otherCategory = Category::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/products",
            $this->productPayload(['category_id' => $otherCategory->id]),
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_unauthenticated_user_cannot_create_product(): void
    {
        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/products",
            $this->productPayload(),
        );

        $response->assertStatus(401);
    }

    // ─── Show ────────────────────────────────────────────────────

    public function test_owner_can_show_a_product(): void
    {
        $product = Product::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson(
            "/api/v1/restaurants/{$this->restaurant->id}/products/{$product->id}",
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $product->name);
    }

    public function test_owner_cannot_show_product_from_another_restaurant(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $product = Product::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->getJson(
            "/api/v1/restaurants/{$this->restaurant->id}/products/{$product->id}",
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(404);
    }

    // ─── Update ──────────────────────────────────────────────────

    public function test_owner_can_update_a_product(): void
    {
        $product = Product::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'name' => 'Nome Antigo',
        ]);

        $response = $this->putJson(
            "/api/v1/restaurants/{$this->restaurant->id}/products/{$product->id}",
            ['name' => 'Nome Novo'],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nome Novo')
            ->assertJsonPath('message', 'Product updated successfully.');
    }

    public function test_owner_can_partially_update_a_product(): void
    {
        $product = Product::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'name' => 'Nome Original',
            'price' => 19.90,
        ]);

        $response = $this->putJson(
            "/api/v1/restaurants/{$this->restaurant->id}/products/{$product->id}",
            ['price' => 29.90],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nome Original')
            ->assertJsonPath('data.price', '29.90');
    }

    public function test_owner_cannot_update_product_from_another_restaurant(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $product = Product::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->putJson(
            "/api/v1/restaurants/{$this->restaurant->id}/products/{$product->id}",
            ['name' => 'Hacker'],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(404);
    }

    // ─── Delete ──────────────────────────────────────────────────

    public function test_owner_can_delete_a_product(): void
    {
        $product = Product::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/restaurants/{$this->restaurant->id}/products/{$product->id}",
            [],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Product deleted successfully.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_owner_cannot_delete_product_from_another_restaurant(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $product = Product::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->deleteJson(
            "/api/v1/restaurants/{$this->restaurant->id}/products/{$product->id}",
            [],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_delete_product(): void
    {
        $product = Product::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/restaurants/{$this->restaurant->id}/products/{$product->id}",
        );

        $response->assertStatus(401);
    }
}
