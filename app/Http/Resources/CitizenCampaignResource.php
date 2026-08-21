<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitizenCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attendance = $this->relationLoaded('asistencias') ? $this->asistencias->first() : null;
        $turno = $this->relationLoaded('turnos')
            ? $this->turnos->first(fn ($item) => ! $attendance?->especialidad_id || $item->especialidad_id === $attendance->especialidad_id)
            : null;

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'fecha' => $this->fecha?->format('Y-m-d'),
            'ubicacion' => $this->ubicacion,
            'estado' => $this->estado,
            'especialidades' => EspecialidadResource::collection($this->whenLoaded('especialidades')),
            'mi_asistencia' => $attendance?->estado,
            'mi_especialidad' => $attendance?->especialidad ? [
                'id' => $attendance->especialidad->id,
                'nombre' => $attendance->especialidad->nombre,
            ] : null,
            'mi_turno' => $turno ? [
                'id' => $turno->id,
                'numero_turno' => $turno->numero_turno,
                'estado' => $turno->estado,
                'especialidad' => $turno->especialidad ? [
                    'id' => $turno->especialidad->id,
                    'nombre' => $turno->especialidad->nombre,
                ] : null,
            ] : null,
            'confirmado_at' => $attendance?->updated_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
