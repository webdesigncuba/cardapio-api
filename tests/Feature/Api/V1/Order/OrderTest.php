<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Order;

use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    private string $token;

    private Restaurant $restaurant;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->token = $this->user->createToken('auth-token')->plainTextToken;
        $this->restaurant = Restaurant::factory()->create(['tenant_id' => $tenant->id]);
        $this->product = Product::factory()->create(['restaurant_id' => $this->restaurant->id]);
    }

    private function orderPayload(array $overrides = []): array
    {
        return array_merge([
            'order_number' => 'ORD-00001',
            'status' => 'pending',
            'subtotal' => 50.00,
            'tax' => 8.00,
            'discount' => 0,
            'total' => 58.00,
            'notes' => 'Sem cebola, por favor',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 25.00,
                    'subtotal' => 50.00,
                    'notes' => 'Bem passado',
                ],
            ],
        ], $overrides);
    }

    // ─── List ────────────────────────────────────────────────────

    public function test_owner_can_list_orders(): void
    {
        Order::factory()->count(3)->create([
            'restaurant_id' => $this->restaurant->id,
        ]);

        // Order from another restaurant
        $otherRestaurant = Restaurant::factory()->create();
        Order::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->getJson("/api/v1/restaurants/{$this->restaurant->id}/orders", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['*' => ['id', 'order_number', 'status', 'total']]]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_unauthenticated_user_cannot_list_orders(): void
    {
        $response = $this->getJson("/api/v1/restaurants/{$this->restaurant->id}/orders");

        $response->assertStatus(401);
    }

    // ─── Create ──────────────────────────────────────────────────

    public function test_owner_can_create_an_order(): void
    {
        $payload = $this->orderPayload();

        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/orders",
            $payload,
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(201)
            ->assertJsonPath('data.order_number', 'ORD-00001')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('message', 'Order created successfully.');

        $this->assertDatabaseHas('orders', [
            'order_number' => 'ORD-00001',
            'restaurant_id' => $this->restaurant->id,
        ]);

        // Check items were created
        $this->assertDatabaseHas('order_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
    }

    public function test_owner_cannot_create_order_without_required_fields(): void
    {
        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/orders",
            [],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_number']);
    }

    public function test_owner_cannot_create_order_with_invalid_status(): void
    {
        $payload = $this->orderPayload(['status' => 'invalid_status']);

        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/orders",
            $payload,
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_owner_cannot_create_order_with_duplicate_order_number(): void
    {
        Order::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'order_number' => 'ORD-00001',
        ]);

        $payload = $this->orderPayload(['order_number' => 'ORD-00001']);

        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/orders",
            $payload,
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_number']);
    }

    public function test_unauthenticated_user_cannot_create_order(): void
    {
        $response = $this->postJson(
            "/api/v1/restaurants/{$this->restaurant->id}/orders",
            $this->orderPayload(),
        );

        $response->assertStatus(401);
    }

    // ─── Show ────────────────────────────────────────────────────

    public function test_owner_can_show_an_order(): void
    {
        $order = Order::factory()->create(['restaurant_id' => $this->restaurant->id]);

        $response = $this->getJson(
            "/api/v1/restaurants/{$this->restaurant->id}/orders/{$order->id}",
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.order_number', $order->order_number);
    }

    public function test_owner_cannot_show_order_from_another_restaurant(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $order = Order::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->getJson(
            "/api/v1/restaurants/{$this->restaurant->id}/orders/{$order->id}",
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(404);
    }

    // ─── Update ──────────────────────────────────────────────────

    public function test_owner_can_update_an_order(): void
    {
        $order = Order::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'status' => 'pending',
            'notes' => 'Nota antiga',
        ]);

        $response = $this->putJson(
            "/api/v1/restaurants/{$this->restaurant->id}/orders/{$order->id}",
            ['status' => 'confirmed', 'notes' => 'Nota nova'],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.notes', 'Nota nova')
            ->assertJsonPath('message', 'Order updated successfully.');
    }

    public function test_owner_can_partially_update_an_order(): void
    {
        $order = Order::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'status' => 'pending',
            'notes' => 'Nota qualquer',
        ]);

        $response = $this->putJson(
            "/api/v1/restaurants/{$this->restaurant->id}/orders/{$order->id}",
            ['status' => 'preparing'],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'preparing');
    }

    public function test_owner_cannot_update_order_from_another_restaurant(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $order = Order::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->putJson(
            "/api/v1/restaurants/{$this->restaurant->id}/orders/{$order->id}",
            ['status' => 'cancelled'],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(404);
    }

    // ─── Delete ──────────────────────────────────────────────────

    public function test_owner_can_delete_an_order(): void
    {
        $order = Order::factory()->create(['restaurant_id' => $this->restaurant->id]);

        $response = $this->deleteJson(
            "/api/v1/restaurants/{$this->restaurant->id}/orders/{$order->id}",
            [],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Order deleted successfully.');

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_owner_cannot_delete_order_from_another_restaurant(): void
    {
        $otherRestaurant = Restaurant::factory()->create();
        $order = Order::factory()->create(['restaurant_id' => $otherRestaurant->id]);

        $response = $this->deleteJson(
            "/api/v1/restaurants/{$this->restaurant->id}/orders/{$order->id}",
            [],
            ['Authorization' => "Bearer {$this->token}"],
        );

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_delete_order(): void
    {
        $order = Order::factory()->create(['restaurant_id' => $this->restaurant->id]);

        $response = $this->deleteJson(
            "/api/v1/restaurants/{$this->restaurant->id}/orders/{$order->id}",
        );

        $response->assertStatus(401);
    }
}