<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrigadaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:5', 'max:150', 'regex:/^[\p{L}\d\s\.,#-]+$/u'],
            'descripcion' => ['sometimes', 'nullable', 'string'],
            'fecha' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:+2 years'],
            'ubicacion' => ['required', 'string', 'min:5', 'max:200', 'regex:/^[\p{L}\d\s\.,#-]+$/u'],
            'estado' => ['sometimes', 'in:programada,en_curso,finalizada,cancelada'],
            'especialidades' => ['required', 'array', 'min:1'],
            'especialidades.*.id' => ['required', 'integer', 'exists:especialidades,id'],
            'especialidades.*.cupos' => ['required', 'integer', 'min:1'],
        ];
    }
}
