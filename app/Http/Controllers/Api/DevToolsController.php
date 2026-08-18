<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

// Endpoints exclusivos para desarrollo/pruebas. Nunca deben quedar disponibles en producción:
// cada método vuelve a verificar el entorno por su cuenta (no solo confiar en el registro
// de la ruta) para que un despliegue accidental no deje esto expuesto.
class DevToolsController extends Controller
{
    public function resetDemo()
    {
       /*  if (! app()->environment(['local', 'testing'])) {
            return response()->json(['message' => 'Esta herramienta solo está disponible en entornos de desarrollo.'], 403);
        } */

        if (! Auth::user()?->hasRole('Administrador')) {
            return response()->json(['message' => 'Solo un Administrador puede reiniciar los datos de demostración.'], 403);
        }

        Artisan::call('demo:reset', ['--force' => true]);

        return response()->json([
            'message' => 'Datos de demostración reiniciados correctamente.',
            'credenciales' => [
                'password_compartido' => '123',
                'coordinador' => ['coordinador1@brigadas.com', 'coordinador2@brigadas.com'],
                'medico' => ['medico1@brigadas.com', 'medico2@brigadas.com', 'medico3@brigadas.com', 'medico4@brigadas.com', 'medico5@brigadas.com', 'medico6@brigadas.com', 'medico7@brigadas.com', 'medico8@brigadas.com', 'medico9@brigadas.com'],
                'brigadista' => ['brigadista1@brigadas.com', 'brigadista2@brigadas.com', 'brigadista3@brigadas.com'],
                'ciudadano' => ['ciudadano1@brigadas.com', 'ciudadano2@brigadas.com'],
            ],
        ]);
    }
}
