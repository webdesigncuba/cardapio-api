<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Category|null $category */
        $category = $this->route('category');
        $restaurant = $this->route('restaurant');
        $isUpdate = $category !== null;

        return [
            'name' => $isUpdate
                ? ['sometimes', 'required', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')
                    ->where(fn ($q) => $q->where('restaurant_id', $restaurant?->id))
                    ->ignore($category),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
