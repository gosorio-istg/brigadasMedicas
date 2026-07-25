<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignarMedicosRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'medicos' => ['required', 'array'],
            'medicos.*' => ['integer', 'exists:medicos,id'],
        ];
    }
}
