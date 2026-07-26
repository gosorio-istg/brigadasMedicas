<?php

namespace App\Http\Requests;

use App\Rules\CedulaEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePacienteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cedula' => ['sometimes', 'required', 'string', 'size:10', new CedulaEcuatoriana, Rule::unique('pacientes', 'cedula')->ignore($this->route('paciente'))],
            'nombres' => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[\p{L}\s\.\'-]+$/u'],
            'apellidos' => ['sometimes', 'required', 'string', 'max:100', 'regex:/^[\p{L}\s\.\'-]+$/u'],
            'fecha_nacimiento' => ['sometimes', 'required', 'date', 'before:today'],
            'sexo' => ['sometimes', 'required', 'in:masculino,femenino,otro'],
            'telefono' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9]{7,10}$/'],
            'sector' => ['sometimes', 'nullable', 'string', 'min:3', 'max:150', 'regex:/^[\p{L}\d\s\.,-]+$/u'],
        ];
    }
}
