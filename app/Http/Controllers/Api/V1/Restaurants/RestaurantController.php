<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Restaurants;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Restaurant\RestaurantRequest;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the authenticated user's restaurants.
     */
    public function index(Request $request): JsonResponse
    {
        $restaurants = Restaurant::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $restaurants,
        ]);
    }

    /**
     * Store a newly created restaurant under the authenticated user's tenant.
     */
    public function store(RestaurantRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $restaurant = Restaurant::create([
            'tenant_id' => $request->user()->tenant_id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'data' => $restaurant,
            'message' => 'Restaurant created successfully.',
        ], 201);
    }

    /**
     * Display the specified restaurant.
     */
    public function show(Restaurant $restaurant): JsonResponse
    {
        return response()->json([
            'data' => $restaurant,
        ]);
    }

    /**
     * Update the specified restaurant.
     */
    public function update(RestaurantRequest $request, Restaurant $restaurant): JsonResponse
    {
        $validated = $request->validated();

        $restaurant->update([
            'name' => $validated['name'] ?? $restaurant->name,
            'slug' => isset($validated['name'])
                ? Str::slug($validated['name'])
                : $restaurant->slug,
            'phone' => $validated['phone'] ?? $restaurant->phone,
            'email' => $validated['email'] ?? $restaurant->email,
            'address' => $validated['address'] ?? $restaurant->address,
            'is_active' => $validated['is_active'] ?? $restaurant->is_active,
        ]);

        return response()->json([
            'data' => $restaurant,
            'message' => 'Restaurant updated successfully.',
        ]);
    }

    /**
     * Remove the specified restaurant from storage.
     */
    public function destroy(Restaurant $restaurant): JsonResponse
    {
        $restaurant->delete();

        return response()->json([
            'message' => 'Restaurant deleted successfully.',
        ], 200);
    }
}
