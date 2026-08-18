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
            'user_id' => $this->user_id,
            'nombres' => $this->nombres,
            'credencial_cmp' => $this->credencial_cmp,
            'telefono' => $this->telefono,
            'disponible' => $this->disponible,
            'especialidad' => $this->whenLoaded('especialidad', fn () => [
                'id' => $this->especialidad->id,
                'nombre' => $this->especialidad->nombre,
            ]),
            // Antes solo mandaba id/nombre: no alcanzaba para que el médico supiera en la app
            // cuáles de sus campañas están en curso, programadas, etc. sin abrir cada una.
            'brigadas' => $this->whenLoaded('brigadas', fn () => $this->brigadas->map(fn ($brigada) => [
                'id' => $brigada->id,
                'nombre' => $brigada->nombre,
                'estado' => $brigada->estado,
                'fecha' => $brigada->fecha?->format('Y-m-d'),
                'ubicacion' => $brigada->ubicacion,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
