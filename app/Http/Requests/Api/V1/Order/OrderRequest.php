<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Order|null $order */
        $order = $this->route('order');
        $restaurant = $this->route('restaurant');
        $isUpdate = $order !== null;

        $rules = [
            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')
                    ->where('restaurant_id', $restaurant?->id),
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'order_number' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                Rule::unique('orders', 'order_number')
                    ->where(fn ($q) => $q->where('restaurant_id', $restaurant?->id))
                    ->ignore($order),
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled']),
            ],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'tax' => ['sometimes', 'numeric', 'min:0'],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],

            // Items anidados (solo aplican en store)
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => [
                'required_with:items',
                'integer',
                Rule::exists('products', 'id')
                    ->where('restaurant_id', $restaurant?->id),
            ],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.subtotal' => ['sometimes', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],

            // Modificadores de cada item
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.modifiers.*.modifier_option_id' => [
                'required_with:items.*.modifiers',
                'integer',
                Rule::exists('modifier_options', 'id'),
            ],
            'items.*.modifiers.*.price' => ['sometimes', 'numeric', 'min:0'],
        ];

        if ($isUpdate) {
            // En update no permitimos cambiar items desde este request
            unset(
                $rules['items'],
                $rules['items.*.product_id'],
                $rules['items.*.quantity'],
                $rules['items.*.unit_price'],
                $rules['items.*.subtotal'],
                $rules['items.*.notes'],
                $rules['items.*.modifiers'],
                $rules['items.*.modifiers.*.modifier_option_id'],
                $rules['items.*.modifiers.*.price'],
            );
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'items.required' => 'La orden debe contener al menos un producto.',
            'items.min' => 'La orden debe contener al menos un producto.',
            'items.*.product_id.required_with' => 'Cada item debe tener un producto asociado.',
            'items.*.product_id.exists' => 'El producto especificado no existe en este restaurante.',
            'items.*.quantity.required_with' => 'Cada item debe tener una cantidad.',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
            'items.*.unit_price.required_with' => 'Cada item debe tener un precio unitario.',
            'items.*.unit_price.min' => 'El precio unitario no puede ser negativo.',
            'status.in' => 'El estado debe ser uno de: pending, confirmed, preparing, ready, delivered, cancelled.',
        ];
    }
}