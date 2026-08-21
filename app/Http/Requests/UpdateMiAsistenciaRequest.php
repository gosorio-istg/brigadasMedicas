<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMiAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $brigada = $this->route('brigada');

        return [
            'estado' => ['required', 'in:asistira,tal_vez,no_asistira'],
            'especialidad_id' => [
                // Confirmar asistencia implica expresar qué atención se necesita.
                // Para las otras respuestas la especialidad se elimina en el controlador.
                Rule::requiredIf(fn () => $this->input('estado') === 'asistira'),
                'nullable',
                'integer',
                Rule::exists('brigada_especialidad', 'especialidad_id')
                    ->where(fn ($query) => $query->where('brigada_id', $brigada?->id)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'especialidad_id.required' => 'Selecciona la especialidad en la que deseas recibir atención.',
            'especialidad_id.exists' => 'La especialidad seleccionada no está disponible en esta campaña.',
        ];
    }
}
