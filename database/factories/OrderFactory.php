<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 500);
        $tax = round($subtotal * 0.16, 2);
        $discount = fake()->optional(0.3)->randomFloat(2, 0, 50) ?? 0;
        $total = round($subtotal + $tax - $discount, 2);

        return [
            'restaurant_id' => RestaurantFactory::new(),
            'client_id' => ClientFactory::new(),
            'user_id' => UserFactory::new(),
            'order_number' => fake()->unique()->numerify('ORD-#####'),
            'status' => fake()->randomElement(['pending', 'confirmed', 'preparing', 'ready', 'delivered']),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'notes' => fake()->optional()->sentence(),
            'estimated_minutes' => fake()->optional()->numberBetween(5, 60),
            'is_active' => true,
        ];
    }
}
