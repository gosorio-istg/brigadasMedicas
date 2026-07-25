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
        ];
    }
}
