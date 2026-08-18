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
        if (! app()->environment(['local', 'testing'])) {
            return response()->json(['message' => 'Esta herramienta solo está disponible en entornos de desarrollo.'], 403);
        }

        if (! Auth::user()?->hasRole('Administrador')) {
            return response()->json(['message' => 'Solo un Administrador puede reiniciar los datos de demostración.'], 403);
        }

        Artisan::call('demo:reset', ['--force' => true]);

        return response()->json([
            'message' => 'Datos de demostración reiniciados correctamente.',
            'credenciales' => [
                'password_compartido' => 'password123',
                'coordinador' => ['coordinador@brigadasalud.test', 'coordinador2@brigadasalud.test'],
                'medico' => ['medico1@brigadasalud.test', 'medico2@brigadasalud.test', 'medico3@brigadasalud.test', 'medico4@brigadasalud.test', 'medico5@brigadasalud.test', 'medico6@brigadasalud.test', 'medico7@brigadasalud.test', 'medico8@brigadasalud.test', 'medico9@brigadasalud.test'],
                'brigadista' => ['brigadista1@brigadasalud.test', 'brigadista2@brigadasalud.test', 'brigadista3@brigadasalud.test'],
                'ciudadano' => ['ciudadano1@brigadasalud.test', 'ciudadano2@brigadasalud.test'],
            ],
        ]);
    }
}
