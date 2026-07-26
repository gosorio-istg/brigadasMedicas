<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateMeRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        $identificador = $credentials['login'];

        $user = filter_var($identificador, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $identificador)->first()
            : User::where('cedula', $identificador)->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (! $user->activo) {
            throw ValidationException::withMessages([
                'login' => ['Esta cuenta se encuentra inactiva.'],
            ]);
        }

        $token = $user->createToken('brigadamedica-token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('roles.permissions')),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load('roles.permissions'));
    }

    // Autoservicio: cualquier usuario autenticado puede editar sus propios datos,
    // sin el permiso "usuarios.editar" que sí exige UserController::update() para editar a otros.
    // No acepta "activo" ni "roles" para evitar que un usuario se autoasigne privilegios.
    public function updateMe(UpdateMeRequest $request)
    {
        $user = $request->user();
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
        $user->save();

        return new UserResource($user->load('roles.permissions'));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
