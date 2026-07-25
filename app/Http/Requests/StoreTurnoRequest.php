<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTurnoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'brigada_id' => ['required', 'integer', 'exists:brigadas,id'],
            'especialidad_id' => ['required', 'integer', 'exists:especialidades,id'],

            // Registro asistido: se envía paciente_id (paciente ya existe) o el bloque
            // "paciente" con sus datos para crearlo en la misma petición.
            'paciente_id' => ['required_without:paciente', 'integer', 'exists:pacientes,id'],
            'paciente' => ['required_without:paciente_id', 'array'],
            'paciente.cedula' => ['required_with:paciente', 'string', 'max:10'],
            'paciente.nombres' => ['required_with:paciente', 'string', 'max:100'],
            'paciente.apellidos' => ['required_with:paciente', 'string', 'max:100'],
            'paciente.fecha_nacimiento' => ['required_with:paciente', 'date', 'before:today'],
            'paciente.sexo' => ['required_with:paciente', 'in:masculino,femenino,otro'],
            'paciente.telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'paciente.sector' => ['sometimes', 'nullable', 'string', 'max:150'],
        ];
    }
}
