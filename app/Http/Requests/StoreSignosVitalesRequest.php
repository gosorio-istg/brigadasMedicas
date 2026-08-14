<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSignosVitalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'presion_arterial' => ['required', 'string', 'max:20', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'temperatura' => ['required', 'numeric', 'between:30,45'],
            'frecuencia_cardiaca' => ['sometimes', 'nullable', 'integer', 'between:20,250'],
            'frecuencia_respiratoria' => ['sometimes', 'nullable', 'integer', 'between:5,80'],
        ];
    }
}
