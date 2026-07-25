<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComunidadRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:150', Rule::unique('comunidades', 'nombre')->ignore($this->route('comunidad'))],
            'sector' => ['sometimes', 'required', 'string', 'max:150'],
            'referencia_ubicacion' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
