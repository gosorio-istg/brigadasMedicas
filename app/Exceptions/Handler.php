<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    // Mensaje "no encontrado/a" por modelo, ya con la concordancia de género correcta
    // (ej. "Comunidad no encontrada" vs "Usuario no encontrado").
    private const MENSAJES_NO_ENCONTRADO = [
        'User' => 'Usuario no encontrado.',
        'Role' => 'Rol no encontrado.',
        'Permission' => 'Permiso no encontrado.',
        'Brigada' => 'Brigada no encontrada.',
        'Especialidad' => 'Especialidad no encontrada.',
        'Paciente' => 'Paciente no encontrado.',
        'Turno' => 'Turno no encontrado.',
        'Medico' => 'Médico no encontrado.',
        'Comunidad' => 'Comunidad no encontrada.',
        'Noticia' => 'Noticia no encontrada.',
        'SolicitudBrigada' => 'Solicitud de brigada no encontrada.',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Todo el proyecto es una API bajo /api/v1: nunca debe devolver la página HTML de error
        // de Laravel, siempre un JSON con un mensaje claro en español.
        $this->renderable(function (AuthenticationException $e, Request $request) {
            if ($this->esPeticionApi($request)) {
                return response()->json([
                    'message' => 'No autenticado. Debes iniciar sesión para acceder a este recurso.',
                ], 401);
            }
        });

        $this->renderable(function (UnauthorizedException $e, Request $request) {
            if ($this->esPeticionApi($request)) {
                return response()->json([
                    'message' => 'No tienes permiso para realizar esta acción.',
                ], 403);
            }
        });

        // Laravel convierte ModelNotFoundException (falla el route-model-binding, ej. /pacientes/999)
        // en NotFoundHttpException antes de llegar aquí, conservando la excepción original en getPrevious().
        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            if (! $this->esPeticionApi($request)) {
                return null;
            }

            $anterior = $e->getPrevious();
            if ($anterior instanceof ModelNotFoundException) {
                $modelo = class_basename($anterior->getModel());
                $mensaje = self::MENSAJES_NO_ENCONTRADO[$modelo] ?? "{$modelo} no encontrado.";

                return response()->json(['message' => $mensaje], 404);
            }

            return response()->json(['message' => 'La ruta solicitada no existe.'], 404);
        });

        $this->renderable(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($this->esPeticionApi($request)) {
                return response()->json([
                    'message' => 'Método HTTP no permitido para esta ruta.',
                ], 405);
            }
        });
    }

    private function esPeticionApi(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }
}
