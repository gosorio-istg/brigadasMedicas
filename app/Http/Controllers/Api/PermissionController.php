<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Resources\PermissionResource;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    // Catálogo casi estático que se recarga en cada formulario de Roles; cachearlo evita
    // pegarle a la base de datos en cada carga de esa pantalla.
    private const CACHE_KEY = 'permissions.index';

    public function index()
    {
        $permissions = Cache::remember(self::CACHE_KEY, 3600, fn () => Permission::all());

        return PermissionResource::collection($permissions);
    }

    public function store(StorePermissionRequest $request)
    {
        $permission = Permission::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        Cache::forget(self::CACHE_KEY);

        return new PermissionResource($permission);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        Cache::forget(self::CACHE_KEY);

        return response()->json([
            'message' => 'Permiso eliminado correctamente.',
        ]);
    }
}
