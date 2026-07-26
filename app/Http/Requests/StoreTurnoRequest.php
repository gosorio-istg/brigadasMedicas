<?php

namespace App\Http\Requests;

use App\Rules\CedulaEcuatoriana;
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
            // Antes no exigía "unique": un registro asistido podía duplicar la cédula de
            // un paciente que ya existía. Mismas reglas que Store/UpdatePacienteRequest.
            'paciente.cedula' => ['required_with:paciente', 'string', 'size:10', new CedulaEcuatoriana, 'unique:pacientes,cedula'],
            'paciente.nombres' => ['required_with:paciente', 'string', 'max:100', 'regex:/^[\p{L}\s\.\'-]+$/u'],
            'paciente.apellidos' => ['required_with:paciente', 'string', 'max:100', 'regex:/^[\p{L}\s\.\'-]+$/u'],
            'paciente.fecha_nacimiento' => ['required_with:paciente', 'date', 'before:today'],
            'paciente.sexo' => ['required_with:paciente', 'in:masculino,femenino,otro'],
            'paciente.telefono' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9]{7,10}$/'],
            'paciente.sector' => ['sometimes', 'nullable', 'string', 'min:3', 'max:150', 'regex:/^[\p{L}\d\s\.,-]+$/u'],
        ];
    }
}
