<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudBrigadaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_solicitante' => $this->nombre_solicitante,
            'telefono_contacto' => $this->telefono_contacto,
            'sector' => $this->sector,
            'especialidades_solicitadas' => $this->especialidades_solicitadas,
            'motivo' => $this->motivo,
            'estado' => $this->estado,
            'notas_coordinador' => $this->notas_coordinador,
            'brigada' => $this->whenLoaded('brigada', fn () => $this->brigada ? [
                'id' => $this->brigada->id,
                'nombre' => $this->brigada->nombre,
            ] : null),
            'gestionado_por' => $this->whenLoaded('gestionadoPor', fn () => $this->gestionadoPor ? [
                'id' => $this->gestionadoPor->id,
                'name' => $this->gestionadoPor->name,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
