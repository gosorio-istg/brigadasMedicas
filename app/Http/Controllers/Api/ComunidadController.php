<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComunidadRequest;
use App\Http\Requests\UpdateComunidadRequest;
use App\Http\Resources\ComunidadResource;
use App\Models\Comunidad;

class ComunidadController extends Controller
{
    public function index()
    {
        return ComunidadResource::collection(Comunidad::orderBy('nombre')->paginate(15));
    }

    public function store(StoreComunidadRequest $request)
    {
        $comunidad = Comunidad::create($request->validated());

        return new ComunidadResource($comunidad);
    }

    public function show(Comunidad $comunidad)
    {
        return new ComunidadResource($comunidad);
    }

    public function update(UpdateComunidadRequest $request, Comunidad $comunidad)
    {
        $comunidad->update($request->validated());

        return new ComunidadResource($comunidad);
    }

    public function destroy(Comunidad $comunidad)
    {
        $comunidad->delete();

        return response()->json(['message' => 'Comunidad eliminada correctamente.']);
    }
}
