<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Clients\ClientRequest;
use App\Models\Client;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    /**
     * Display a listing of clients for the given restaurant.
     */
    public function index(Restaurant $restaurant): JsonResponse
    {
    $clients = Client::where('restaurant_id', $restaurant->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $clients,
        ]);
    }

    /**
     * Store a newly created client for the given restaurant.
     */
    public function store(ClientRequest $request, Restaurant $restaurant): JsonResponse
    {
        $client = Client::create([
            ...$request->validated(),
            'restaurant_id' => $restaurant->id,
        ]);

        return response()->json([
            'data' => $client,
            'message' => 'Client created successfully.',
        ], 201);
    }

    /**
     * Display the specified client.
     */
    public function show(Restaurant $restaurant, Client $client): JsonResponse
    {
        $this->ensureBelongsToRestaurant($restaurant, $client);

        return response()->json([
            'data' => $client,
        ]);
    }

    /**
     * Update the specified client.
     */
    public function update(ClientRequest $request, Restaurant $restaurant, Client $client): JsonResponse
    {

        $this->ensureBelongsToRestaurant($restaurant, $client);

        $client->update($request->validated());

        return response()->json([
            'data' => $client->fresh(),
            'message' => 'Client updated successfully.',
        ]);
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy(Restaurant $restaurant, Client $client): JsonResponse
    {
        $this->ensureBelongsToRestaurant($restaurant, $client);

        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully.',
        ]);
    }

    /**
     * Ensure the client belongs to the given restaurant.
     */
    private function ensureBelongsToRestaurant(Restaurant $restaurant, Client $client): void
    {
        abort_if($client->restaurant_id !== $restaurant->id, 404);
    }

    public function getRestaurant($id)
    {
        $restaurant = Restaurant::find($id);
        if(!$restaurant){
            return response()->json([
                'message' => "Restaurant no encontrado",
            ], 404);
        }
    }
}
