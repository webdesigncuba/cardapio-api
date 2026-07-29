<?php

namespace Database\Factories;

use App\Models\OrderItemModifier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemModifier>
 */
class OrderItemModifierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItemFactory::new(),
            'modifier_option_id' => ModifierOptionFactory::new(),
            'price' => fake()->randomFloat(2, 0, 20),
        ];
    }
}
