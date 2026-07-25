<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTurnoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:pendiente,en_espera,atendido,cancelado'],
            'medico_id' => ['sometimes', 'nullable', 'integer', 'exists:medicos,id'],
        ];
    }
}
