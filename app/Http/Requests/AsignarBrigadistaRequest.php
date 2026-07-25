<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignarBrigadistaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'rol_equipo' => ['required', 'in:registro,apoyo_logistico,atencion_medica,coordinacion'],
        ];
    }
}
