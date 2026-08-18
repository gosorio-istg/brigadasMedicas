<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Envuelve un array de agregados (no un modelo Eloquent): $this->resource es el array armado
// en ReporteController::resumen(). Se usa JsonResource igual que en el resto del proyecto
// para no romper la convención de "nunca devolver arrays crudos".
class ReporteResumenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'brigada_id' => $this->resource['brigada_id'],
            'total_turnos' => $this->resource['total_turnos'],
            'total_atendidos' => $this->resource['total_atendidos'],
            'tiempo_promedio_espera_minutos' => $this->resource['tiempo_promedio_espera_minutos'],
            'desglose_por_especialidad' => collect($this->resource['desglose_por_especialidad'])->map(fn ($fila) => [
                'especialidad_id' => (int) $fila->especialidad_id,
                'especialidad' => $fila->especialidad,
                'total' => (int) $fila->total,
                'atendidos' => (int) $fila->atendidos,
            ])->values(),
            'desglose_por_estado' => $this->resource['desglose_por_estado'],
            'tendencia_semanal' => $this->resource['tendencia_semanal'],
        ];
    }
}
