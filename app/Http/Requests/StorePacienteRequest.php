<?php

namespace App\Http\Requests;

use App\Rules\CedulaEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;

class StorePacienteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cedula' => ['required', 'string', 'size:10', new CedulaEcuatoriana, 'unique:pacientes,cedula'],
            'nombres' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s\.\'-]+$/u'],
            'apellidos' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s\.\'-]+$/u'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'sexo' => ['required', 'in:masculino,femenino,otro'],
            'telefono' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9]{7,10}$/'],
            'sector' => ['sometimes', 'nullable', 'string', 'min:3', 'max:150', 'regex:/^[\p{L}\d\s\.,-]+$/u'],
        ];
    }
}
