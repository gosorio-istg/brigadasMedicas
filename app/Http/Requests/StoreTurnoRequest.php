<?php

namespace App\Http\Requests;

use App\Rules\CedulaEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTurnoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brigada_id' => ['required', 'integer', 'exists:brigadas,id'],
            'especialidad_id' => ['required', 'integer', 'exists:especialidades,id'],

            // La plataforma web puede seleccionar directamente una cuenta Ciudadano.
            // El controlador materializa o vincula su registro Paciente al asignar el turno.
            'user_id' => ['required_without_all:paciente_id,paciente', 'integer', 'exists:users,id'],

            // Registro asistido: se envía paciente_id (paciente ya existe) o el bloque
            // "paciente" con sus datos para crearlo en la misma petición.
            'paciente_id' => [
                'required_without_all:user_id,paciente', 'integer', 'exists:pacientes,id',
                // Antes no existía este chequeo: se podía registrar al mismo paciente
                // dos veces en la misma campaña y especialidad sin ningún aviso. No
                // aplica si su turno anterior ahí fue cancelado (sí puede volver a pasar).
                Rule::unique('turnos', 'paciente_id')->where(fn ($query) => $query
                    ->where('brigada_id', $this->input('brigada_id'))
                    ->where('especialidad_id', $this->input('especialidad_id'))
                    ->where('estado', '!=', 'cancelado')),
            ],
            'paciente' => ['required_without_all:user_id,paciente_id', 'array'],
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
