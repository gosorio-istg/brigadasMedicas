<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicoRequest;
use App\Http\Requests\UpdateMedicoRequest;
use App\Http\Resources\MedicoResource;
use App\Models\Medico;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MedicoController extends Controller
{
    public function index(Request $request)
    {
        $medicos = Medico::with('especialidad')
            ->orderBy('nombres')
            ->paginate($this->perPage($request));

        return MedicoResource::collection($medicos);
    }

    public function store(StoreMedicoRequest $request)
    {
        $data = $request->validated();

        $medico = DB::transaction(function () use ($data) {
            if (empty($data['user_id'])) {
                $user = User::create([
                    'name' => $data['nombres'],
                    'apellido' => $data['apellido'],
                    'cedula' => $data['cedula'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'activo' => true,
                ]);
                $user->assignRole('Medico');
                $data['user_id'] = $user->id;
            }

            return Medico::create([
                'user_id' => $data['user_id'],
                'nombres' => $data['nombres'],
                'credencial_cmp' => $data['credencial_cmp'],
                'especialidad_id' => $data['especialidad_id'],
                'telefono' => $data['telefono'] ?? null,
                'disponible' => $data['disponible'] ?? true,
            ]);
        });

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
