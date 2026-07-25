<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComunidadRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150', 'unique:comunidades,nombre'],
            'sector' => ['required', 'string', 'max:150'],
            'referencia_ubicacion' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
