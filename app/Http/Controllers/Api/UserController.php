<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')->orderByDesc('created_at')->paginate($this->perPage($request));

        return UserResource::collection($users);
    }

    /** Lista únicamente las cuentas que pueden asignarse como brigadistas. */
    public function brigadistas(Request $request)
    {
        $users = User::role('Brigadista')->with('roles')->orderBy('name')->paginate($this->perPage($request, 30));

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'apellido' => $data['apellido'],
            'cedula' => $data['cedula'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'activo' => $data['activo'] ?? true,
        ]);

        if (! empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return new UserResource($user->load('roles'));
    }

    public function show(User $user)
    {
        return new UserResource($user->load(['roles.permissions', 'permissions']));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        if (isset($data['apellido'])) {
            $user->apellido = $data['apellido'];
        }
        if (isset($data['cedula'])) {
            $user->cedula = $data['cedula'];
        }
        if (isset($data['email'])) {
            $user->email = $data['email'];
        }
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        if (array_key_exists('activo', $data)) {
            $user->activo = $data['activo'];
        }
        $user->save();

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return new UserResource($user->load('roles'));
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}
