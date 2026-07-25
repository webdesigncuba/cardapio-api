<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\ModifierOption;

use App\Models\ModifierOption;
use Illuminate\Foundation\Http\FormRequest;

class ModifierOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ModifierOption|null $option */
        $option = $this->route('option');
        $isUpdate = $option !== null;

        return [
            'name' => $isUpdate
                ? ['sometimes', 'required', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'price_modifier' => ['sometimes', 'numeric', 'min:0'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
