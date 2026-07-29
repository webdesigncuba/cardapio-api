<?php

namespace Database\Factories;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 5, 100);
        $quantity = fake()->numberBetween(1, 5);
        $subtotal = round($unitPrice * $quantity, 2);

        return [
            'order_id' => OrderFactory::new(),
            'product_id' => ProductFactory::new(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
