<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return RoleResource::collection(Role::with('permissions')->get());
    }

    public function store(StoreRoleRequest $request)
    {
        $data = $request->validated();

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        if (! empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return new RoleResource($role->load('permissions'));
    }

    public function show(Role $role)
    {
        return new RoleResource($role->load('permissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $data = $request->validated();

        if (isset($data['name'])) {
            $role->name = $data['name'];
            $role->save();
        }

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return new RoleResource($role->load('permissions'));
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json([
            'message' => 'Rol eliminado correctamente.',
        ]);
    }
}
