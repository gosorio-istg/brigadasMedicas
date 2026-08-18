<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AtencionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'turno_id' => $this->turno_id,
            'diagnostico' => $this->diagnostico,
            'motivo_consulta' => $this->motivo_consulta,
            'tipo_atencion' => $this->tipo_atencion,
            'requiere_referencia' => $this->requiere_referencia,
            'receta' => $this->receta,
            'observaciones' => $this->observaciones,
            'fecha_atencion' => $this->fecha_atencion,
        ];
    }
}
