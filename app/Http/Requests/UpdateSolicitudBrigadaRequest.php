<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSolicitudBrigadaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:pendiente,en_revision,aprobada,rechazada'],
            'notas_coordinador' => ['sometimes', 'nullable', 'string'],
            'brigada_id' => ['sometimes', 'nullable', 'integer', 'exists:brigadas,id'],
        ];
    }
}
