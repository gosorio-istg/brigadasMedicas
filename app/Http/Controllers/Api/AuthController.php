<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateMeRequest;
use App\Http\Resources\UserResource;
use App\Models\Medico;
use App\Models\User;
use App\Rules\CedulaEcuatoriana;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function firebase(Request $request)
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
            'cedula' => ['sometimes', 'nullable', 'string', 'size:10', new CedulaEcuatoriana],
            'nombres' => ['sometimes', 'nullable', 'string', 'max:150'],
            'apellidos' => ['sometimes', 'nullable', 'string', 'max:150'],
            'fecha_nacimiento' => ['sometimes', 'nullable', 'date', 'before:today'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'sector' => ['sometimes', 'nullable', 'string', 'max:150'],
        ]);

        $apiKey = config('services.firebase.web_api_key');
        if (! $apiKey) {
            return response()->json(['message' => 'Firebase no está configurado en el servidor.'], 503);
        }

        try {
            $firebaseResponse = Http::asJson()
                ->timeout(8)
                ->retry(1, 200)
                ->post("https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$apiKey}", [
                    'idToken' => $data['id_token'],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Firebase caído/inalcanzable: no dejar que esto cuelgue el worker ni devuelva un 500 crudo.
            return response()->json(['message' => 'No se pudo verificar la sesión de Firebase. Intenta de nuevo.'], 503);
        }

        $firebaseUser = $firebaseResponse->json('users.0');
        if (! $firebaseResponse->successful() || ! $firebaseUser || empty($firebaseUser['localId']) || empty($firebaseUser['email'])) {
            return response()->json(['message' => 'La sesión de Firebase no es válida o expiró.'], 401);
        }

        $firebaseUid = $firebaseUser['localId'];
        $email = mb_strtolower($firebaseUser['email']);
        $user = User::where('firebase_uid', $firebaseUid)->orWhere('email', $email)->first();

        if (! empty($data['cedula']) && User::where('cedula', $data['cedula'])
            ->when($user, fn ($query) => $query->where('id', '!=', $user->id))->exists()) {
            return response()->json(['message' => 'Los datos de identidad ya pertenecen a otra cuenta.'], 422);
        }

        if (! $user) {
            $user = User::create([
                'name' => $data['nombres'] ?: ($firebaseUser['displayName'] ?? 'Ciudadano'),
                'apellido' => $data['apellidos'] ?? null,
                'cedula' => $data['cedula'] ?? null,
                'email' => $email,
                'firebase_uid' => $firebaseUid,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'sector' => $data['sector'] ?? null,
                'password' => Str::random(48),
                'activo' => true,
            ]);
            $user->assignRole('Ciudadano');
        } elseif ($user->firebase_uid && $user->firebase_uid !== $firebaseUid) {
            return response()->json(['message' => 'El correo ya está vinculado con otra cuenta de Firebase.'], 409);
        } elseif (! $user->firebase_uid) {
            $user->forceFill(['firebase_uid' => $firebaseUid])->save();
        }

        if (! $user->activo) {
            return response()->json(['message' => 'Esta cuenta se encuentra inactiva.'], 403);
        }

        $user->tokens()->where('name', 'brigadasalud-android')->delete();
        $token = $user->createToken('brigadasalud-android')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load(['roles.permissions', 'permissions'])),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

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
            'user' => new UserResource($user->load(['roles.permissions', 'permissions'])),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load(['roles.permissions', 'permissions']));
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
        if (array_key_exists('telefono', $data)) {
            $user->telefono = $data['telefono'];
        }
        if (array_key_exists('sector', $data)) {
            $user->sector = $data['sector'];
        }
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return new UserResource($user->load(['roles.permissions', 'permissions']));
    }

    public function updateMedicalAvailability(Request $request)
    {
        $data = $request->validate(['disponible' => ['required', 'boolean']]);
        $medico = Medico::where('user_id', $request->user()->id)->first();
        if (! $medico) {
            return response()->json(['message' => 'La cuenta no está vinculada con un registro médico.'], 404);
        }
        $medico->update(['disponible' => $data['disponible']]);

        return response()->json(['data' => ['disponible' => $medico->disponible]]);
    }

    public function medicalAvailability(Request $request)
    {
        $medico = Medico::where('user_id', $request->user()->id)->first();
        if (! $medico) {
            return response()->json(['message' => 'La cuenta no está vinculada con un registro médico.'], 404);
        }

        return response()->json(['data' => ['disponible' => $medico->disponible]]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
