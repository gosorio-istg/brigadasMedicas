<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrigadaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'min:5', 'max:150', 'regex:/^[\p{L}\d\s\.,#-]+$/u'],
            'descripcion' => ['sometimes', 'nullable', 'string'],
            // Sin cota inferior a propósito (a diferencia de Store): permite corregir la
            // fecha de una campaña que ya pasó. La cota superior sí aplica siempre.
            'fecha' => ['sometimes', 'required', 'date', 'before_or_equal:+2 years'],
            'ubicacion' => ['sometimes', 'required', 'string', 'min:5', 'max:200', 'regex:/^[\p{L}\d\s\.,#-]+$/u'],
            'estado' => ['sometimes', 'in:programada,en_curso,finalizada,cancelada'],
            'especialidades' => ['sometimes', 'array', 'min:1'],
            'especialidades.*.id' => ['required_with:especialidades', 'integer', 'exists:especialidades,id'],
            'especialidades.*.cupos' => ['required_with:especialidades', 'integer', 'min:1'],
        ];
    }
}
