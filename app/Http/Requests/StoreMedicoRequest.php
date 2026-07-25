<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:150'],
            'credencial_cmp' => ['required', 'string', 'max:50', 'unique:medicos,credencial_cmp'],
            'especialidad_id' => ['required', 'integer', 'exists:especialidades,id'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'disponible' => ['sometimes', 'boolean'],
        ];
    }
}
