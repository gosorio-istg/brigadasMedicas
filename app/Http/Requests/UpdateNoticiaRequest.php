<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoticiaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:200'],
            'resumen' => ['sometimes', 'required', 'string', 'max:255'],
            'contenido' => ['sometimes', 'required', 'string'],
            'imagen_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'fecha_publicacion' => ['sometimes', 'required', 'date'],
            'publicada' => ['sometimes', 'boolean'],
        ];
    }
}
