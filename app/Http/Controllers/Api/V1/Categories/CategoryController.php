<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Categories;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Category\CategoryRequest;
// Removed direct Category import to avoid undefined type issues; using relations to fetch models.
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories for the given restaurant.
     */
    public function index(Restaurant $restaurant): JsonResponse
    {
        $categories = $restaurant->categories()
            ->orderBy('sort_order')
            ->latest('id')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created category for the given restaurant.
     */
    public function store(CategoryRequest $request, Restaurant $restaurant): JsonResponse
    {
        $validated = $request->validated();

        $category = $restaurant->categories()->create([
            ...$validated,
            'slug' => $validated['slug'] ?? str($validated['name'])->slug()->toString(),
        ]);

        return response()->json([
            'data' => $category,
            'message' => 'Category created successfully.',
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(Restaurant $restaurant, $categoryId): JsonResponse
    {
        $category = $restaurant->categories()->findOrFail($categoryId);

        return response()->json([
            'data' => $category,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(CategoryRequest $request, Restaurant $restaurant, $categoryId): JsonResponse
    {
        $category = $restaurant->categories()->findOrFail($categoryId);

        $validated = $request->validated();

        $category->update([
            ...$validated,
            'slug' => isset($validated['name'])
                ? str($validated['name'])->slug()->toString()
                : $category->slug,
        ]);

        return response()->json([
            'data' => $category->fresh(),
            'message' => 'Category updated successfully.',
        ]);
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Restaurant $restaurant, $categoryId): JsonResponse
    {
        $category = $restaurant->categories()->findOrFail($categoryId);

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
