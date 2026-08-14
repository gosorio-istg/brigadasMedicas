<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAtencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diagnostico' => ['required', 'string', 'max:5000'],
            'receta' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'observaciones' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
