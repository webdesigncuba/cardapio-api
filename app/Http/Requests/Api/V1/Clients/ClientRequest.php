<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Clients;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client');
        $isUpdate = $clientId !== null;

        return [
            'name' => $isUpdate
                ? ['sometimes', 'required', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'address' => $isUpdate
                ? ['sometimes', 'required', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'number' => $isUpdate
                ? ['sometimes', 'required', 'string', 'max:50']
                : ['required', 'string', 'max:50'],
            'cep' => $isUpdate
                ? ['sometimes', 'required', 'string', 'max:20']
                : ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'uf' => $isUpdate
                ? ['sometimes', 'required', 'string', 'size:2']
                : ['required', 'string', 'size:2'],
            'bario' => $isUpdate
                ? ['sometimes', 'required', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
        ];
    }
}
