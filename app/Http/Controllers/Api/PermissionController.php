<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Resources\PermissionResource;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        return PermissionResource::collection(Permission::all());
    }

    public function store(StorePermissionRequest $request)
    {
        $permission = Permission::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        return new PermissionResource($permission);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return response()->json([
            'message' => 'Permiso eliminado correctamente.',
        ]);
    }
}
