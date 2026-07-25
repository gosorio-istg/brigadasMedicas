<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReporteResumenResource;
use App\Http\Resources\ReporteSectorResource;
use App\Models\Turno;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function resumen(Request $request)
    {
        $query = Turno::query()
            ->when($request->filled('brigada_id'), fn ($q) => $q->where('brigada_id', $request->brigada_id));

        $totalTurnos = (clone $query)->count();
        $totalAtendidos = (clone $query)->where('estado', 'atendido')->count();

        // Minutos promedio entre hora_registro y hora_atencion, solo turnos ya atendidos.
        $tiempoPromedioMinutos = (clone $query)
            ->where('estado', 'atendido')
            ->whereNotNull('hora_atencion')
            ->get(['hora_registro', 'hora_atencion'])
            ->avg(fn ($turno) => $turno->hora_registro->diffInMinutes($turno->hora_atencion));

        $desglose = (clone $query)
            ->join('especialidades', 'especialidades.id', '=', 'turnos.especialidad_id')
            ->selectRaw('especialidades.id as especialidad_id, especialidades.nombre as especialidad, count(*) as total, sum(case when turnos.estado = "atendido" then 1 else 0 end) as atendidos')
            ->groupBy('especialidades.id', 'especialidades.nombre')
            ->orderBy('especialidades.nombre')
            ->get();

        return new ReporteResumenResource([
            'brigada_id' => $request->filled('brigada_id') ? (int) $request->brigada_id : null,
            'total_turnos' => $totalTurnos,
            'total_atendidos' => $totalAtendidos,
            'tiempo_promedio_espera_minutos' => $tiempoPromedioMinutos !== null ? round($tiempoPromedioMinutos, 1) : null,
            'desglose_por_especialidad' => $desglose,
        ]);
    }

    public function porSector(Request $request)
    {
        // El módulo Comunidades todavía no existe, así que se agrupa por Paciente.sector (texto libre).
        // Cuando exista comunidad_id (ver sección 6.5 del contexto), este reporte debe agrupar por esa FK.
        $porSector = Turno::query()
            ->join('pacientes', 'pacientes.id', '=', 'turnos.paciente_id')
            ->when($request->filled('brigada_id'), fn ($q) => $q->where('turnos.brigada_id', $request->brigada_id))
            ->selectRaw('COALESCE(pacientes.sector, "Sin sector") as sector, count(*) as total_turnos, sum(case when turnos.estado = "atendido" then 1 else 0 end) as total_atendidos, count(distinct pacientes.id) as pacientes_unicos')
            ->groupBy('sector')
            ->orderByDesc('total_turnos')
            ->get();

        return ReporteSectorResource::collection($porSector);
    }
}
