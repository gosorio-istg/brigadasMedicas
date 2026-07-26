<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EspecialidadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'activa' => $this->activa,
            'cupos' => $this->whenPivotLoaded('brigada_especialidad', fn () => $this->pivot->cupos),
            // Solo viene calculado cuando el controlador lo adjunta explícitamente (hoy solo
            // BrigadaController::show()); en el resto de contextos se omite del JSON.
            'cupos_ocupados' => $this->when(isset($this->pivot->cupos_ocupados), fn () => $this->pivot->cupos_ocupados),
        ];
    }
}
