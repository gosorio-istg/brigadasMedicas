<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTurnoRequest;
use App\Http\Requests\UpdateTurnoRequest;
use App\Http\Resources\TurnoResource;
use App\Models\Brigada;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Turno;
use App\Services\SyncOutboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TurnoController extends Controller
{
    public function index(Request $request)
    {
        $turnos = Turno::with(['paciente', 'brigada', 'especialidad', 'registrador', 'medico', 'signosVitales', 'atencion'])
            ->when($request->filled('brigada_id'), fn ($q) => $q->where('brigada_id', $request->brigada_id))
            ->when($request->filled('especialidad_id'), fn ($q) => $q->where('especialidad_id', $request->especialidad_id))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->orderBy('hora_registro')
            ->paginate(20);

        return TurnoResource::collection($turnos);
    }

    public function store(StoreTurnoRequest $request, SyncOutboxService $sync)
    {
        $data = $request->validated();

        $brigada = Brigada::findOrFail($data['brigada_id']);
        $especialidad = Especialidad::findOrFail($data['especialidad_id']);

        // La especialidad debe estar ofrecida por la brigada (tiene un registro en el pivot con sus cupos).
        $pivot = $brigada->especialidades()->where('especialidades.id', $especialidad->id)->first();

        if (! $pivot) {
            return response()->json([
                'message' => 'La brigada no ofrece la especialidad indicada.',
            ], 422);
        }

        $paciente = isset($data['paciente_id'])
            ? Paciente::findOrFail($data['paciente_id'])
            : Paciente::firstOrCreate(
                ['cedula' => $data['paciente']['cedula']],
                [
                    'nombres' => $data['paciente']['nombres'],
                    'apellidos' => $data['paciente']['apellidos'],
                    'fecha_nacimiento' => $data['paciente']['fecha_nacimiento'],
                    'sexo' => $data['paciente']['sexo'],
                    'telefono' => $data['paciente']['telefono'] ?? null,
                    'sector' => $data['paciente']['sector'] ?? null,
                ]
            );

        // lockForUpdate() bloquea las filas de turnos ya existentes de esta brigada+especialidad
        // mientras dura la transacción, evitando que dos registros simultáneos generen el mismo consecutivo.
        $turno = DB::transaction(function () use ($brigada, $especialidad, $paciente, $sync) {
            $consecutivo = Turno::where('brigada_id', $brigada->id)
                ->where('especialidad_id', $especialidad->id)
                ->lockForUpdate()
                ->count() + 1;

            $turno = Turno::create([
                'brigada_id' => $brigada->id,
                'paciente_id' => $paciente->id,
                'especialidad_id' => $especialidad->id,
                'numero_turno' => $this->generarNumeroTurno($especialidad, $consecutivo),
                'estado' => 'pendiente',
                'registrado_por' => Auth::id(),
                'hora_registro' => now(),
            ]);
            $sync->queue('turnos_realtime', $turno->id, $this->turnoRealtimePayload($turno));

            return $turno;
        });

        $resource = new TurnoResource($turno->load(['paciente', 'brigada', 'especialidad', 'registrador', 'medico']));

        // Regla de cupos: no se bloquea la creación, solo se advierte si ya se alcanzó el tope.
        $turnosActivos = Turno::where('brigada_id', $brigada->id)
            ->where('especialidad_id', $especialidad->id)
            ->where('estado', '!=', 'cancelado')
            ->count();

        if ($turnosActivos > $pivot->pivot->cupos) {
            return $resource->additional([
                'advertencia' => 'Se superó el cupo disponible para esta especialidad en la brigada.',
            ]);
        }

        return $resource;
    }

    public function update(UpdateTurnoRequest $request, Turno $turno, SyncOutboxService $sync)
    {
        $data = $request->validated();

        $turno->estado = $data['estado'];
        if ($data['estado'] === 'atendido' && ! $turno->hora_atencion) {
            $turno->hora_atencion = now();
        }
        if (array_key_exists('medico_id', $data)) {
            $turno->medico_id = $data['medico_id'];
        }
        $turno->save();
        $sync->queue('turnos_realtime', $turno->id, $this->turnoRealtimePayload($turno));

        return new TurnoResource($turno->load(['paciente', 'brigada', 'especialidad', 'registrador', 'medico']));
    }

    // Genera un prefijo a partir de las iniciales de la especialidad (ej. "Medicina General" -> "MG").
    private function generarNumeroTurno(Especialidad $especialidad, int $consecutivo): string
    {
        $palabras = preg_split('/\s+/', trim($especialidad->nombre));

        $prefijo = count($palabras) > 1
            ? mb_substr(collect($palabras)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode(''), 0, 3)
            : mb_strtoupper(mb_substr($palabras[0], 0, 3));

        return $prefijo.'-'.str_pad((string) $consecutivo, 3, '0', STR_PAD_LEFT);
    }

    private function turnoRealtimePayload(Turno $turno): array
    {
        return [
            'turno_id' => $turno->id,
            'numero_turno' => $turno->numero_turno,
            'estado' => $turno->estado,
            'brigada_id' => $turno->brigada_id,
            'especialidad_id' => $turno->especialidad_id,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
