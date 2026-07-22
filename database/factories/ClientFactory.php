<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'restaurant_id' => RestaurantFactory::new(),
            'name' => fake()->name(),
            'address' => fake()->streetAddress(),
            'number' => fake()->buildingNumber(),
            'cep' => fake()->postcode(),
            'complement' => fake()->optional()->secondaryAddress(),
            'uf' => fake()->stateAbbr(),
            'bario' => fake()->citySuffix(),
        ];
    }
}
