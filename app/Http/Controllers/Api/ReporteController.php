<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReporteResumenResource;
use App\Http\Resources\ReporteSectorResource;
use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReporteController extends Controller
{
    public function resumen(Request $request)
    {
        $query = Turno::query()
            ->when($request->filled('brigada_id'), fn ($q) => $q->where('brigada_id', $request->brigada_id));

        $totalTurnos = (clone $query)->count();
        $totalAtendidos = (clone $query)->where('estado', 'atendido')->count();

        // Minutos promedio entre hora_registro y hora_atencion, solo turnos ya atendidos.
        // Se agrega en SQL (en vez de traer cada turno a PHP y promediar ahí) para que esto
        // siga siendo rápido cuando la tabla turnos crezca a miles de filas.
        $tiempoPromedioMinutos = (clone $query)
            ->where('estado', 'atendido')
            ->whereNotNull('hora_atencion')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, hora_registro, hora_atencion)) as promedio')
            ->value('promedio');

        $desglose = (clone $query)
            ->join('especialidades', 'especialidades.id', '=', 'turnos.especialidad_id')
            ->selectRaw('especialidades.id as especialidad_id, especialidades.nombre as especialidad, count(*) as total, sum(case when turnos.estado = "atendido" then 1 else 0 end) as atendidos')
            ->groupBy('especialidades.id', 'especialidades.nombre')
            ->orderBy('especialidades.nombre')
            ->get();

        // Para el gráfico de "Turnos por estado": cuenta fija de los 5 estados posibles,
        // en vez de solo los que aparecen en la tabla, para que el frontend no tenga que
        // adivinar cuáles faltan y el gráfico no salte de tamaño según los datos.
        $conteosPorEstado = (clone $query)
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');
        $desglosePorEstado = collect(['pendiente', 'en_espera', 'atendido', 'cancelado', 'no_asistio'])
            ->map(fn ($estado) => ['estado' => $estado, 'total' => (int) ($conteosPorEstado[$estado] ?? 0)])
            ->values();

        // Actividad de los últimos 7 días (incluye hoy): se rellenan en PHP los días sin
        // turnos para que el gráfico de tendencia siempre muestre una semana completa en
        // vez de saltarse fechas sin registros.
        $desde = Carbon::today()->subDays(6);
        $porDia = (clone $query)
            ->where('hora_registro', '>=', $desde->copy()->startOfDay())
            ->selectRaw('DATE(hora_registro) as fecha, count(*) as total, sum(case when estado = "atendido" then 1 else 0 end) as atendidos')
            ->groupBy('fecha')
            ->get()
            ->keyBy('fecha');
        $tendenciaSemanal = collect(range(0, 6))->map(function (int $offset) use ($desde, $porDia) {
            $fecha = $desde->copy()->addDays($offset)->toDateString();
            $fila = $porDia->get($fecha);

            return ['fecha' => $fecha, 'total' => (int) ($fila->total ?? 0), 'atendidos' => (int) ($fila->atendidos ?? 0)];
        });

        return new ReporteResumenResource([
            'brigada_id' => $request->filled('brigada_id') ? (int) $request->brigada_id : null,
            'total_turnos' => $totalTurnos,
            'total_atendidos' => $totalAtendidos,
            'tiempo_promedio_espera_minutos' => $tiempoPromedioMinutos !== null ? round($tiempoPromedioMinutos, 1) : null,
            'desglose_por_especialidad' => $desglose,
            'desglose_por_estado' => $desglosePorEstado,
            'tendencia_semanal' => $tendenciaSemanal,
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
