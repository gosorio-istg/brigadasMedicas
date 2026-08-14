<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitizenCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attendance = $this->relationLoaded('asistencias') ? $this->asistencias->first() : null;

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'fecha' => $this->fecha?->format('Y-m-d'),
            'ubicacion' => $this->ubicacion,
            'estado' => $this->estado,
            'especialidades' => EspecialidadResource::collection($this->whenLoaded('especialidades')),
            'mi_asistencia' => $attendance?->estado,
            'confirmado_at' => $attendance?->updated_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
