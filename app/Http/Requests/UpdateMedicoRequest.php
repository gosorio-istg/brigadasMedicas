<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombres' => ['sometimes', 'required', 'string', 'max:150', 'regex:/^[\p{L}\s\.\'-]+$/u'],
            'credencial_cmp' => ['sometimes', 'required', 'string', 'regex:/^CMP-\d{4}$/', Rule::unique('medicos', 'credencial_cmp')->ignore($this->route('medico'))],
            'especialidad_id' => ['sometimes', 'required', 'integer', 'exists:especialidades,id'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'disponible' => ['sometimes', 'boolean'],
        ];
    }
}
