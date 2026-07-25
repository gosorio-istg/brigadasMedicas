<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicoRequest;
use App\Http\Requests\UpdateMedicoRequest;
use App\Http\Resources\MedicoResource;
use App\Models\Medico;

class MedicoController extends Controller
{
    public function index()
    {
        $medicos = Medico::with('especialidad')
            ->orderBy('nombres')
            ->paginate(15);

        return MedicoResource::collection($medicos);
    }

    public function store(StoreMedicoRequest $request)
    {
        $medico = Medico::create($request->validated());

        return new MedicoResource($medico->load('especialidad'));
    }

    public function show(Medico $medico)
    {
        return new MedicoResource($medico->load(['especialidad', 'brigadas']));
    }

    public function update(UpdateMedicoRequest $request, Medico $medico)
    {
        $medico->update($request->validated());

        return new MedicoResource($medico->load('especialidad'));
    }

    public function destroy(Medico $medico)
    {
        $medico->delete();

        return response()->json(['message' => 'Médico eliminado correctamente.']);
    }
}
