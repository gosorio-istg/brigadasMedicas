<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id', 'unique:medicos,user_id'],
            'nombres' => ['required', 'string', 'max:150', 'regex:/^[\p{L}\s\.\'-]+$/u'],
            'credencial_cmp' => ['required', 'string', 'regex:/^CMP-\d{4}$/', 'unique:medicos,credencial_cmp'],
            'especialidad_id' => ['required', 'integer', 'exists:especialidades,id'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'disponible' => ['sometimes', 'boolean'],
        ];
    }
}
