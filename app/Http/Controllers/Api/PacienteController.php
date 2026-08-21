<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePacienteRequest;
use App\Http\Requests\UpdatePacienteRequest;
use App\Http\Resources\PacienteResource;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    /**
     * Busca en historias clínicas y cuentas Ciudadano con una sola consulta para
     * que el formulario de turnos también encuentre a quienes acaban de registrarse.
     */
    public function candidatos(Request $request)
    {
        $data = $request->validate([
            'buscar' => ['required', 'string', 'min:3', 'max:150'],
        ]);
        $buscar = $data['buscar'];

        $pacientes = Paciente::with('user:id')
            ->where(function ($query) use ($buscar) {
                $query->where('cedula', 'like', "%{$buscar}%")
                    ->orWhere('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%");
            })
            ->limit(10)
            ->get();

        $ciudadanos = User::role('Ciudadano')
            ->with('paciente:id,user_id')
            ->where(function ($query) use ($buscar) {
                $query->where('cedula', 'like', "%{$buscar}%")
                    ->orWhere('name', 'like', "%{$buscar}%")
                    ->orWhere('apellido', 'like', "%{$buscar}%");
            })
            ->limit(10)
            ->get();

        $resultados = $pacientes->map(fn (Paciente $paciente) => [
            'paciente_id' => $paciente->id,
            'user_id' => $paciente->user_id,
            'cedula' => $paciente->cedula,
            'nombres' => $paciente->nombres,
            'apellidos' => $paciente->apellidos,
            'origen' => $paciente->user_id ? 'Ciudadano y paciente' : 'Paciente asistido',
        ])->keyBy('cedula');

        foreach ($ciudadanos as $ciudadano) {
            if (! $ciudadano->cedula || $resultados->has($ciudadano->cedula)) {
                continue;
            }
            $resultados->put($ciudadano->cedula, [
                'paciente_id' => $ciudadano->paciente?->id,
                'user_id' => $ciudadano->id,
                'cedula' => $ciudadano->cedula,
                'nombres' => $ciudadano->name,
                'apellidos' => $ciudadano->apellido ?? '',
                'origen' => 'Ciudadano registrado en la app',
            ]);
        }

        return response()->json(['data' => $resultados->values()]);
    }

    public function index(Request $request)
    {
        $pacientes = Paciente::query()
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $buscar = $request->string('buscar');
                $query->where(function ($q) use ($buscar) {
                    $q->where('cedula', 'like', "%{$buscar}%")
                        ->orWhere('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request));

        return PacienteResource::collection($pacientes);
    }

    public function store(StorePacienteRequest $request)
    {
        $paciente = Paciente::create($request->validated());

        return new PacienteResource($paciente);
    }

    public function show(Paciente $paciente)
    {
        return new PacienteResource($paciente->load([
            'turnos.brigada', 'turnos.especialidad', 'turnos.signosVitales', 'turnos.atencion',
        ]));
    }

    public function update(UpdatePacienteRequest $request, Paciente $paciente)
    {
        $paciente->update($request->validated());

        return new PacienteResource($paciente);
    }
}
