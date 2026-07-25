<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePacienteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cedula' => ['required', 'string', 'max:10', 'unique:pacientes,cedula'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'sexo' => ['required', 'in:masculino,femenino,otro'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'sector' => ['sometimes', 'nullable', 'string', 'max:150'],
        ];
    }
}
