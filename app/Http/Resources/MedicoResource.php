<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombres' => $this->nombres,
            'credencial_cmp' => $this->credencial_cmp,
            'telefono' => $this->telefono,
            'disponible' => $this->disponible,
            'especialidad' => $this->whenLoaded('especialidad', fn () => [
                'id' => $this->especialidad->id,
                'nombre' => $this->especialidad->nombre,
            ]),
            'brigadas' => $this->whenLoaded('brigadas', fn () => $this->brigadas->map(fn ($brigada) => [
                'id' => $brigada->id,
                'nombre' => $brigada->nombre,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
