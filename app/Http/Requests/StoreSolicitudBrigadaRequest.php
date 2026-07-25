<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Endpoint público (sin autenticación): lo llena un ciudadano desde la app/web,
// por eso no depende de Auth::id() ni de ningún dato de sesión.
class StoreSolicitudBrigadaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre_solicitante' => ['required', 'string', 'max:150'],
            'telefono_contacto' => ['required', 'string', 'max:20'],
            'sector' => ['required', 'string', 'max:150'],
            'especialidades_solicitadas' => ['sometimes', 'nullable', 'string', 'max:255'],
            'motivo' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
