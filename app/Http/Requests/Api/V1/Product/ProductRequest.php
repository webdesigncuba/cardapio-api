<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Product;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');
        $restaurant = $this->route('restaurant');
        $isUpdate = $product !== null;

        return [
            'category_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('restaurant_id', $restaurant?->id),
            ],
            'name' => $isUpdate
                ? ['sometimes', 'required', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('products', 'slug')
                    ->where(fn ($q) => $q->where('restaurant_id', $restaurant?->id))
                    ->ignore($product),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => $isUpdate
                ? ['sometimes', 'required', 'numeric', 'min:0']
                : ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'string', 'max:255'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
