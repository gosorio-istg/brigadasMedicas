<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrigadaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:150'],
            'descripcion' => ['sometimes', 'nullable', 'string'],
            'fecha' => ['sometimes', 'required', 'date'],
            'ubicacion' => ['sometimes', 'required', 'string', 'max:200'],
            'estado' => ['sometimes', 'in:programada,en_curso,finalizada,cancelada'],
            'especialidades' => ['sometimes', 'array', 'min:1'],
            'especialidades.*.id' => ['required_with:especialidades', 'integer', 'exists:especialidades,id'],
            'especialidades.*.cupos' => ['required_with:especialidades', 'integer', 'min:1'],
        ];
    }
}
