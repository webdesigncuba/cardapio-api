<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Modifier;

use App\Models\Modifier;
use Illuminate\Foundation\Http\FormRequest;

class ModifierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Modifier|null $modifier */
        $modifier = $this->route('modifier');
        $isUpdate = $modifier !== null;

        return [
            'name' => $isUpdate
                ? ['sometimes', 'required', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'is_required' => ['boolean'],
            'max_options' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
