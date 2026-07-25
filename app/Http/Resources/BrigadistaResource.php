<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Envuelve un User (con pivot de brigada_brigadista cargado) al listar el equipo de una brigada.
class BrigadistaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol_equipo' => $this->pivot->rol_equipo,
            'asistio' => $this->pivot->asistio,
        ];
    }
}
