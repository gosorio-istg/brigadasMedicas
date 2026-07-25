<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Envuelve cada fila (stdClass) del resultado agregado por sector de ReporteController::porSector().
class ReporteSectorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sector' => $this->sector,
            'total_turnos' => (int) $this->total_turnos,
            'total_atendidos' => (int) $this->total_atendidos,
            'pacientes_unicos' => (int) $this->pacientes_unicos,
        ];
    }
}
