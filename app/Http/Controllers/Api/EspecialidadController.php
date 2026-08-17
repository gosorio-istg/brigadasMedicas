<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEspecialidadRequest;
use App\Http\Resources\EspecialidadResource;
use App\Models\Especialidad;
use Illuminate\Support\Facades\Cache;

class EspecialidadController extends Controller
{
    // Catálogo casi estático que se recarga en casi todos los formularios (turnos, campañas,
    // médicos); cachearlo evita pegarle a la base de datos en cada carga de esas pantallas.
    private const CACHE_KEY = 'especialidades.index';

    public function index()
    {
        $especialidades = Cache::remember(self::CACHE_KEY, 3600, fn () => Especialidad::orderBy('nombre')->get());

        return EspecialidadResource::collection($especialidades);
    }

    public function store(StoreEspecialidadRequest $request)
    {
        $especialidad = Especialidad::create([
            'nombre' => $request->validated('nombre'),
            'activa' => $request->validated('activa') ?? true,
        ]);

        Cache::forget(self::CACHE_KEY);

        return new EspecialidadResource($especialidad);
    }

    public function destroy(Especialidad $especialidad)
    {
        $especialidad->delete();

        Cache::forget(self::CACHE_KEY);

        return response()->json(['message' => 'Especialidad eliminada correctamente.']);
    }
}
