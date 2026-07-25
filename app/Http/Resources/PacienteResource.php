<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PacienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cedula' => $this->cedula,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'fecha_nacimiento' => $this->fecha_nacimiento?->format('Y-m-d'),
            'edad' => $this->fecha_nacimiento?->age,
            'sexo' => $this->sexo,
            'telefono' => $this->telefono,
            'sector' => $this->sector,
            'turnos' => $this->whenLoaded('turnos', fn () => TurnoResource::collection($this->turnos)),
            'created_at' => $this->created_at,
        ];
    }
}
