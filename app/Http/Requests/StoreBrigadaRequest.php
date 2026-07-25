<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrigadaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['sometimes', 'nullable', 'string'],
            'fecha' => ['required', 'date'],
            'ubicacion' => ['required', 'string', 'max:200'],
            'estado' => ['sometimes', 'in:programada,en_curso,finalizada,cancelada'],
            'especialidades' => ['required', 'array', 'min:1'],
            'especialidades.*.id' => ['required', 'integer', 'exists:especialidades,id'],
            'especialidades.*.cupos' => ['required', 'integer', 'min:1'],
        ];
    }
}
