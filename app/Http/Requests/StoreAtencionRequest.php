<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAtencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diagnostico' => ['required', 'string', 'max:5000'],
            'motivo_consulta' => ['required', 'in:enfermedad_comun,control,chequeo_preventivo,urgencia,seguimiento,otro'],
            'tipo_atencion' => ['required', 'in:primera_vez,seguimiento'],
            'requiere_referencia' => ['sometimes', 'boolean'],
            'receta' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'observaciones' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
