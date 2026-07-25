<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePreferenciaRequest;
use App\Http\Resources\PreferenciaResource;
use Illuminate\Http\Request;

class PreferenciaController extends Controller
{
    // Cada usuario administra sus propias preferencias; no requiere permiso especial,
    // solo estar autenticado. Se crea con valores por defecto la primera vez que se piden.
    public function show(Request $request)
    {
        return new PreferenciaResource($this->preferenciaDelUsuario($request));
    }

    public function update(UpdatePreferenciaRequest $request)
    {
        $preferencia = $this->preferenciaDelUsuario($request);
        $preferencia->update($request->validated());

        return new PreferenciaResource($preferencia);
    }

    // firstOrCreate([]) sin valores por defecto deja el modelo en memoria con esos
    // atributos en null hasta que se vuelve a consultar (aunque la BD ya aplicó el
    // default de la migración); por eso se pasan explícitos para que la respuesta
    // sea correcta también en la primera creación.
    private function preferenciaDelUsuario(Request $request)
    {
        return $request->user()->preferencia()->firstOrCreate([], [
            'notificaciones_email' => true,
            'notificaciones_push' => true,
        ]);
    }
}
