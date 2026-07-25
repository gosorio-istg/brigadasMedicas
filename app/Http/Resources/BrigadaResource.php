<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrigadaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'fecha' => $this->fecha?->format('Y-m-d'),
            'ubicacion' => $this->ubicacion,
            'estado' => $this->estado,
            'coordinador' => $this->whenLoaded('coordinador', fn () => [
                'id' => $this->coordinador->id,
                'name' => $this->coordinador->name,
            ]),
            'especialidades' => $this->whenLoaded('especialidades', fn () => EspecialidadResource::collection($this->especialidades)),
            'created_at' => $this->created_at,
        ];
    }
}
