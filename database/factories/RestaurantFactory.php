<?php

namespace Database\Factories;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => TenantFactory::new(),
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(2),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'address' => fake()->optional()->address(),
            'is_active' => true,
        ];
    }
}
