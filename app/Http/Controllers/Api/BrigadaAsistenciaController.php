<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMiAsistenciaRequest;
use App\Http\Resources\CitizenCampaignResource;
use App\Models\Brigada;
use App\Models\BrigadaAsistencia;
use App\Models\Paciente;
use App\Models\Turno;
use Illuminate\Http\Request;

class BrigadaAsistenciaController extends Controller
{
    /** Campañas visibles para el ciudadano, incluyendo su respuesta personal. */
    public function myCampaigns(Request $request)
    {
        $paciente = Paciente::where('user_id', $request->user()->id)
            ->when($request->user()->cedula, fn ($query) => $query->orWhere('cedula', $request->user()->cedula))
            ->first();

        $campaigns = Brigada::with([
            'especialidades',
            'asistencias' => fn ($query) => $query
                ->where('user_id', $request->user()->id)
                ->with('especialidad:id,nombre'),
            'turnos' => fn ($query) => $query
                ->where('paciente_id', $paciente?->id ?? 0)
                ->where('estado', '!=', 'cancelado')
                ->with('especialidad:id,nombre')
                ->latest('hora_registro'),
        ])
            ->whereIn('estado', ['programada', 'en_curso'])
            ->orderByDesc('fecha')
            ->paginate($this->perPage($request, 30));

        return CitizenCampaignResource::collection($campaigns);
    }

    public function index(Brigada $brigada)
    {
        $items = $brigada->asistencias()
            ->with(['user:id,name,apellido,cedula', 'user.paciente:id,user_id', 'especialidad:id,nombre'])
            ->latest('updated_at')
            ->get();

        $pacientesPorUsuario = Paciente::whereIn('user_id', $items->pluck('user_id'))
            ->get(['id', 'user_id'])
            ->keyBy('user_id');

        $turnos = Turno::with('especialidad:id,nombre')
            ->where('brigada_id', $brigada->id)
            ->whereIn('paciente_id', $pacientesPorUsuario->pluck('id'))
            ->where('estado', '!=', 'cancelado')
            ->latest('hora_registro')
            ->get()
            ->keyBy(fn (Turno $turno) => "{$turno->paciente_id}:{$turno->especialidad_id}");

        $data = $items->map(function (BrigadaAsistencia $item) use ($pacientesPorUsuario, $turnos) {
            $paciente = $pacientesPorUsuario->get($item->user_id);
            $turno = $paciente && $item->especialidad_id
                ? $turnos->get("{$paciente->id}:{$item->especialidad_id}")
                : null;

            return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'nombre' => trim($item->user->name.' '.($item->user->apellido ?? '')),
                'estado' => $item->estado,
                'especialidad' => $item->especialidad ? [
                    'id' => $item->especialidad->id,
                    'nombre' => $item->especialidad->nombre,
                ] : null,
                'turno' => $this->resumenTurno($turno),
                'updated_at' => $item->updated_at,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function show(Request $request, Brigada $brigada)
    {
        $asistencia = BrigadaAsistencia::whereBelongsTo($brigada)
            ->whereBelongsTo($request->user())
            ->with('especialidad:id,nombre')
            ->first();
        $paciente = Paciente::where('user_id', $request->user()->id)
            ->when($request->user()->cedula, fn ($query) => $query->orWhere('cedula', $request->user()->cedula))
            ->first();
        $turno = $paciente
            ? Turno::with('especialidad:id,nombre')
                ->where('brigada_id', $brigada->id)
                ->where('paciente_id', $paciente->id)
                ->when($asistencia?->especialidad_id, fn ($query) => $query->where('especialidad_id', $asistencia->especialidad_id))
                ->where('estado', '!=', 'cancelado')
                ->latest('hora_registro')
                ->first()
            : null;

        $data = $asistencia?->toArray();
        if ($data !== null) {
            $data['turno'] = $this->resumenTurno($turno);
        }

        return response()->json(['data' => $data]);
    }

    public function update(UpdateMiAsistenciaRequest $request, Brigada $brigada)
    {
        $estado = $request->validated('estado');
        $paciente = Paciente::where('user_id', $request->user()->id)
            ->when($request->user()->cedula, fn ($query) => $query->orWhere('cedula', $request->user()->cedula))
            ->first();
        $turnoAsignado = $paciente
            ? Turno::with('especialidad:id,nombre')
                ->where('brigada_id', $brigada->id)
                ->where('paciente_id', $paciente->id)
                ->where('estado', '!=', 'cancelado')
                ->latest('hora_registro')
                ->first()
            : null;

        // Cuando el equipo ya emitió un turno, la preinscripción deja de ser una
        // intención editable. Cualquier cambio debe gestionarlo el personal para no
        // dejar un turno activo mientras la app muestra que la persona no asistirá.
        if ($turnoAsignado && ($estado !== 'asistira'
            || (int) $request->validated('especialidad_id') !== $turnoAsignado->especialidad_id)) {
            return response()->json([
                'message' => "Ya tienes asignado el turno {$turnoAsignado->numero_turno}. Solicita al equipo de la brigada cualquier cambio.",
            ], 422);
        }

        $asistencia = BrigadaAsistencia::updateOrCreate(
            ['brigada_id' => $brigada->id, 'user_id' => $request->user()->id],
            [
                'estado' => $estado,
                // Una especialidad solo representa demanda real cuando el ciudadano
                // confirmó que asistirá; las demás respuestas no deben inflar métricas.
                'especialidad_id' => $estado === 'asistira'
                    ? $request->validated('especialidad_id')
                    : null,
            ]
        );

        $asistencia->load('especialidad:id,nombre');

        $data = $asistencia->toArray();
        $data['turno'] = $this->resumenTurno($turnoAsignado);

        return response()->json([
            'data' => $data,
            'message' => match ($asistencia->estado) {
                'asistira' => $turnoAsignado
                    ? "Tu turno {$turnoAsignado->numero_turno} ya está asignado."
                    : "Preinscripción confirmada para {$asistencia->especialidad->nombre}. El turno será visible aquí cuando el equipo lo asigne.",
                'tal_vez' => 'Guardamos tu respuesta. Puedes confirmarla cuando estés seguro.',
                'no_asistira' => 'Gracias por avisarnos. Puedes cambiar tu respuesta si luego puedes asistir.',
            },
        ]);
    }

    private function resumenTurno(?Turno $turno): ?array
    {
        if (! $turno) {
            return null;
        }

        return [
            'id' => $turno->id,
            'numero_turno' => $turno->numero_turno,
            'estado' => $turno->estado,
            'especialidad' => $turno->especialidad ? [
                'id' => $turno->especialidad->id,
                'nombre' => $turno->especialidad->nombre,
            ] : null,
        ];
    }
}
