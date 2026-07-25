<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComunidadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'sector' => $this->sector,
            'referencia_ubicacion' => $this->referencia_ubicacion,
            'created_at' => $this->created_at,
        ];
    }
}
