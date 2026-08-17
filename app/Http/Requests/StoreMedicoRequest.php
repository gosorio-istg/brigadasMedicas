<?php

namespace App\Http\Requests;

use App\Rules\CedulaEcuatoriana;
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
            // Si se manda user_id, el médico se vincula a una cuenta ya existente (p. ej. desde
            // el panel móvil). Si no, estos datos crean la cuenta de acceso del médico aquí mismo.
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id', 'unique:medicos,user_id'],
            'nombres' => ['required', 'string', 'max:150', 'regex:/^[\p{L}\s\.\'-]+$/u'],
            'credencial_cmp' => ['required', 'string', 'regex:/^CMP-\d{4}$/', 'unique:medicos,credencial_cmp'],
            'especialidad_id' => ['required', 'integer', 'exists:especialidades,id'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'disponible' => ['sometimes', 'boolean'],
            'apellido' => ['required_without:user_id', 'nullable', 'string', 'max:150'],
            'cedula' => ['required_without:user_id', 'nullable', 'string', 'size:10', new CedulaEcuatoriana, 'unique:users,cedula'],
            'email' => ['required_without:user_id', 'nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required_without:user_id', 'nullable', 'string', 'min:8'],
        ];
    }
}
