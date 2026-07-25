<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePacienteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cedula' => ['sometimes', 'required', 'string', 'max:10', Rule::unique('pacientes', 'cedula')->ignore($this->route('paciente'))],
            'nombres' => ['sometimes', 'required', 'string', 'max:100'],
            'apellidos' => ['sometimes', 'required', 'string', 'max:100'],
            'fecha_nacimiento' => ['sometimes', 'required', 'date', 'before:today'],
            'sexo' => ['sometimes', 'required', 'in:masculino,femenino,otro'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'sector' => ['sometimes', 'nullable', 'string', 'max:150'],
        ];
    }
}
