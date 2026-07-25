<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEspecialidadRequest;
use App\Http\Resources\EspecialidadResource;
use App\Models\Especialidad;

class EspecialidadController extends Controller
{
    public function index()
    {
        return EspecialidadResource::collection(Especialidad::orderBy('nombre')->get());
    }

    public function store(StoreEspecialidadRequest $request)
    {
        $especialidad = Especialidad::create([
            'nombre' => $request->validated('nombre'),
            'activa' => $request->validated('activa') ?? true,
        ]);

        return new EspecialidadResource($especialidad);
    }

    public function destroy(Especialidad $especialidad)
    {
        $especialidad->delete();

        return response()->json(['message' => 'Especialidad eliminada correctamente.']);
    }
}
