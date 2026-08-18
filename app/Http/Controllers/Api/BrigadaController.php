<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarAsistenciaRequest;
use App\Http\Requests\AsignarBrigadistaRequest;
use App\Http\Requests\AsignarMedicosRequest;
use App\Http\Requests\StoreBrigadaRequest;
use App\Http\Requests\UpdateBrigadaRequest;
use App\Http\Resources\BrigadaResource;
use App\Http\Resources\BrigadistaResource;
use App\Http\Resources\MedicoResource;
use App\Models\Brigada;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrigadaController extends Controller
{
    public function index(Request $request)
    {
        $brigadas = Brigada::with(['coordinador', 'especialidades'])
            ->orderByDesc('fecha')
            ->paginate($this->perPage($request));

        return BrigadaResource::collection($brigadas);
    }

    public function store(StoreBrigadaRequest $request)
    {
        $data = $request->validated();

        $brigada = Brigada::create([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'fecha' => $data['fecha'],
            'ubicacion' => $data['ubicacion'],
            'estado' => $data['estado'] ?? 'programada',
            'coordinador_id' => Auth::id(),
        ]);

        $this->syncEspecialidades($brigada, $data['especialidades']);

        return new BrigadaResource($brigada->load(['coordinador', 'especialidades']));
    }

    public function show(Brigada $brigada)
    {
        $this->ensureCanView($brigada);

        $brigada->load(['coordinador', 'especialidades']);
        $brigada->loadCount([
            'asistencias as asistiran_count' => fn ($query) => $query->where('estado', 'asistira'),
            'asistencias as tal_vez_count' => fn ($query) => $query->where('estado', 'tal_vez'),
            'asistencias as no_asistiran_count' => fn ($query) => $query->where('estado', 'no_asistira'),
        ]);

        // Cupos "ocupados" = turnos ya registrados (no cancelados) para cada especialidad
        // de esta brigada. Se calcula aquí (no en el index, para no hacer N+1 sobre el
        // listado completo de brigadas) y solo se usa en esta respuesta puntual.
        $turnosPorEspecialidad = Turno::where('brigada_id', $brigada->id)
            ->where('estado', '!=', 'cancelado')
            ->selectRaw('especialidad_id, count(*) as total')
            ->groupBy('especialidad_id')
            ->pluck('total', 'especialidad_id');

        foreach ($brigada->especialidades as $especialidad) {
            $especialidad->pivot->cupos_ocupados = $turnosPorEspecialidad[$especialidad->id] ?? 0;
        }

        return new BrigadaResource($brigada);
    }

    public function update(UpdateBrigadaRequest $request, Brigada $brigada)
    {
        $data = $request->validated();

        // No tenía ningún resguardo: se podía finalizar una campaña con pacientes
        // todavía en cola (pendientes o en espera), dejándolos sin ninguna atención
        // registrada. Deben quedar resueltos (atendido/cancelado/no_asistio) antes.
        if (($data['estado'] ?? null) === 'finalizada') {
            $pendientes = $brigada->turnos()->whereIn('estado', ['pendiente', 'en_espera'])->count();

            if ($pendientes > 0) {
                return response()->json([
                    'message' => "No se puede finalizar: todavía hay {$pendientes} turno(s) pendiente(s) o en espera. Márcalos como atendidos, cancelados o \"no asistió\" antes de finalizar la campaña.",
                ], 422);
            }
        }

        // Tampoco tenía resguardo para iniciar: se podía poner "en_curso" sin tener ni un
        // médico asignado para cada especialidad ofrecida, dejando pacientes registrándose
        // en una cola que nadie iba a atender.
        if (($data['estado'] ?? null) === 'en_curso' && $brigada->estado !== 'en_curso') {
            $especialidadesOfrecidas = $brigada->especialidades()->pluck('especialidades.id');
            $especialidadesConMedico = $brigada->medicos()->pluck('especialidad_id')->unique();
            $faltantes = $especialidadesOfrecidas->diff($especialidadesConMedico);

            if ($faltantes->isNotEmpty()) {
                $nombres = Especialidad::whereIn('id', $faltantes)->pluck('nombre')->implode(', ');

                return response()->json([
                    'message' => "No se puede iniciar la campaña: falta asignar un médico para: {$nombres}.",
                ], 422);
            }
        }

        $brigada->fill([
            'nombre' => $data['nombre'] ?? $brigada->nombre,
            'descripcion' => array_key_exists('descripcion', $data) ? $data['descripcion'] : $brigada->descripcion,
            'fecha' => $data['fecha'] ?? $brigada->fecha,
            'ubicacion' => $data['ubicacion'] ?? $brigada->ubicacion,
            'estado' => $data['estado'] ?? $brigada->estado,
        ])->save();

        if (isset($data['especialidades'])) {
            $this->syncEspecialidades($brigada, $data['especialidades']);
        }

        return new BrigadaResource($brigada->load(['coordinador', 'especialidades']));
    }

    public function destroy(Brigada $brigada)
    {
        $brigada->delete();

        return response()->json(['message' => 'Brigada eliminada correctamente.']);
    }

    private function syncEspecialidades(Brigada $brigada, array $especialidades): void
    {
        $sync = [];
        foreach ($especialidades as $item) {
            $sync[$item['id']] = ['cupos' => $item['cupos']];
        }

        $brigada->especialidades()->sync($sync);
    }

    public function medicos(Brigada $brigada)
    {
        $this->ensureCanView($brigada);

        return MedicoResource::collection($brigada->medicos()->with('especialidad')->get());
    }

    // Antes show()/medicos() exigían el permiso brigadas.gestionar/medicos.gestionar (solo
    // Coordinador/Administrador), así que un Médico o Brigadista asignado a la campaña recibía
    // 403 al intentar ver el detalle de su propia campaña desde la app. Estas rutas ahora solo
    // exigen sesión autenticada y este método verifica que el usuario tenga permiso de gestión
    // o esté realmente asignado a la brigada (como médico o como brigadista).
    private function ensureCanView(Brigada $brigada): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->can('brigadas.gestionar')) {
            return;
        }

        $medico = Medico::where('user_id', $user->id)->first();
        if ($medico && $brigada->medicos()->where('medicos.id', $medico->id)->exists()) {
            return;
        }

        if ($brigada->brigadistas()->where('users.id', $user->id)->exists()) {
            return;
        }

        abort(403, 'No tienes acceso a esta campaña.');
    }

    public function asignarMedicos(AsignarMedicosRequest $request, Brigada $brigada)
    {
        $medicoIds = $request->validated('medicos');

        // Antes se podía asignar cualquier médico sin importar su especialidad (ej. un
        // psicólogo a una campaña que solo ofrece Medicina General y Enfermería), lo que
        // generaba turnos que ese médico nunca iba a poder atender.
        $especialidadesOfrecidas = $brigada->especialidades()->pluck('especialidades.id');
        $medicosInvalidos = Medico::whereIn('id', $medicoIds)
            ->whereNotIn('especialidad_id', $especialidadesOfrecidas)
            ->pluck('nombres');

        if ($medicosInvalidos->isNotEmpty()) {
            return response()->json([
                'message' => 'Estos médicos no tienen una especialidad que esta campaña ofrezca: '.$medicosInvalidos->implode(', ').'.',
            ], 422);
        }

        $brigada->medicos()->sync($medicoIds);

        return MedicoResource::collection($brigada->medicos()->with('especialidad')->get());
    }

    public function brigadistas(Brigada $brigada)
    {
        return BrigadistaResource::collection($brigada->brigadistas()->get());
    }

    public function asignarBrigadista(AsignarBrigadistaRequest $request, Brigada $brigada)
    {
        $data = $request->validated();

        // syncWithoutDetaching: agrega o actualiza a este brigadista sin quitar a los demás ya asignados.
        $brigada->brigadistas()->syncWithoutDetaching([
            $data['user_id'] => ['rol_equipo' => $data['rol_equipo']],
        ]);

        return BrigadistaResource::collection($brigada->brigadistas()->get());
    }

    public function actualizarAsistenciaBrigadista(ActualizarAsistenciaRequest $request, Brigada $brigada, User $user)
    {
        $brigada->brigadistas()->updateExistingPivot($user->id, [
            'asistio' => $request->validated('asistio'),
        ]);

        return BrigadistaResource::collection($brigada->brigadistas()->get());
    }

    public function quitarBrigadista(Brigada $brigada, User $user)
    {
        $brigada->brigadistas()->detach($user->id);

        return response()->json(['message' => 'Brigadista removido de la brigada correctamente.']);
    }
}
