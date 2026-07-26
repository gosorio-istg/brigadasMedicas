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
use App\Models\Turno;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class BrigadaController extends Controller
{
    public function index()
    {
        $brigadas = Brigada::with(['coordinador', 'especialidades'])
            ->orderByDesc('fecha')
            ->paginate(15);

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
        $brigada->load(['coordinador', 'especialidades']);

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
        return MedicoResource::collection($brigada->medicos()->with('especialidad')->get());
    }

    public function asignarMedicos(AsignarMedicosRequest $request, Brigada $brigada)
    {
        $brigada->medicos()->sync($request->validated('medicos'));

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
