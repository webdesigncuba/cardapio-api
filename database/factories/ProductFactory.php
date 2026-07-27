<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'restaurant_id' => RestaurantFactory::new(),
            'category_id' => CategoryFactory::new(),
            'name' => ucfirst($name),
            'slug' => str($name)->slug(),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomFloat(2, 1, 100),
            'image' => fake()->optional()->imageUrl(),
            'estimated_minutes' => fake()->optional()->numberBetween(5, 60),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
