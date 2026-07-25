<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferenciaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'notificaciones_email' => ['sometimes', 'boolean'],
            'notificaciones_push' => ['sometimes', 'boolean'],
        ];
    }
}
