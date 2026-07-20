<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\Restaurant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = DB::transaction(function () use ($validated): array {
            $restaurantName = $validated['restaurant_name'];
            $slug = Str::slug($restaurantName);

            $tenant = Tenant::create([
                'name' => $restaurantName,
                'slug' => $slug,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'owner',
            ]);

            $restaurant = Restaurant::create([
                'tenant_id' => $tenant->id,
                'name' => $restaurantName,
                'slug' => $slug,
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            return compact('user', 'tenant', 'restaurant', 'token');
        });

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'role' => $result['user']->role,
                ],
                'restaurant' => [
                    'id' => $result['restaurant']->id,
                    'name' => $result['restaurant']->name,
                    'slug' => $result['restaurant']->slug,
                ],
                'tenant' => [
                    'id' => $result['tenant']->id,
                    'name' => $result['tenant']->name,
                ],
                'token' => $result['token'],
            ],
            'message' => 'Registro exitoso. Bienvenido a Cardapio.',
        ], 201);
    }
}
