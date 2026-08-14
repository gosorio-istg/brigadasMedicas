<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePacienteRequest;
use App\Http\Requests\UpdatePacienteRequest;
use App\Http\Resources\PacienteResource;
use App\Models\Paciente;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
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
            ->orderBy('apellidos')
            ->paginate(15);

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
