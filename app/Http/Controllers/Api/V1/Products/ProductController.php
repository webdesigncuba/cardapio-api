<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\ProductRequest;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(Restaurant $restaurant): JsonResponse
    {
        $products = $restaurant->products()
            ->with('category')
            ->orderBy('sort_order')
            ->latest('id')
            ->get();

        return response()->json(['data' => $products]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function store(ProductRequest $request, Restaurant $restaurant): JsonResponse
    {
        $validated = $request->validated();

        $product = $restaurant->products()->create([
            ...$validated,
            'slug' => $validated['slug'] ?? str($validated['name'])->slug()->toString(),
        ]);

        return response()->json([
            'data' => $product->load('category'),
            'message' => 'Product created successfully.',
        ], 201);
    }

    public function show(Restaurant $restaurant, Product $product): JsonResponse
    {
        abort_if($product->restaurant_id !== $restaurant->id, 404);

        return response()->json(['data' => $product->load('category')]);
    }

    public function update(ProductRequest $request, Restaurant $restaurant, Product $product): JsonResponse
    {
        abort_if($product->restaurant_id !== $restaurant->id, 404);

        $validated = $request->validated();

        $product->update([
            ...$validated,
            'slug' => isset($validated['name'])
                ? str($validated['name'])->slug()->toString()
                : $product->slug,
        ]);

        return response()->json([
            'data' => $product->fresh()->load('category'),
            'message' => 'Product updated successfully.',
        ]);
    }

    public function destroy(Restaurant $restaurant, Product $product): JsonResponse
    {
        abort_if($product->restaurant_id !== $restaurant->id, 404);

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
