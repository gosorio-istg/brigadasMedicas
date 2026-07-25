<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoticiaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:200'],
            'resumen' => ['required', 'string', 'max:255'],
            'contenido' => ['required', 'string'],
            'imagen_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'fecha_publicacion' => ['required', 'date'],
            'publicada' => ['sometimes', 'boolean'],
        ];
    }
}
