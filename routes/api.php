<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrigadaAsistenciaController;
use App\Http\Controllers\Api\BrigadaController;
use App\Http\Controllers\Api\ClinicalRecordController;
use App\Http\Controllers\Api\ComunidadController;
use App\Http\Controllers\Api\EspecialidadController;
use App\Http\Controllers\Api\MedicoController;
use App\Http\Controllers\Api\NoticiaController;
use App\Http\Controllers\Api\PacienteController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PreferenciaController;
use App\Http\Controllers\Api\PublicContentController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SolicitudBrigadaController;
use App\Http\Controllers\Api\TurnoController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/* Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
}); */

Route::prefix('v1')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/auth/firebase', [AuthController::class, 'firebase']);
    Route::get('/public/brigadas', [PublicContentController::class, 'brigadas']);
    Route::get('/public/noticias', [PublicContentController::class, 'noticias']);

    // Público: cualquier ciudadano puede pedir una brigada sin necesidad de una cuenta.
    Route::post('/solicitudes-brigada', [SolicitudBrigadaController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/me/campanas', [BrigadaAsistenciaController::class, 'myCampaigns']);
        Route::put('/me', [AuthController::class, 'updateMe']);
        Route::get('/me/disponibilidad-medica', [AuthController::class, 'medicalAvailability']);
        Route::put('/me/disponibilidad-medica', [AuthController::class, 'updateMedicalAvailability']);
        Route::get('/brigadas/{brigada}/mi-asistencia', [BrigadaAsistenciaController::class, 'show']);
        Route::put('/brigadas/{brigada}/mi-asistencia', [BrigadaAsistenciaController::class, 'update']);

        // Preferencias propias del usuario autenticado: no requiere permiso especial.
        Route::get('/preferencias', [PreferenciaController::class, 'show']);
        Route::put('/preferencias', [PreferenciaController::class, 'update']);

        Route::middleware('permission:usuarios.ver')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{user}', [UserController::class, 'show']);
        });
        Route::post('/users', [UserController::class, 'store'])->middleware('permission:usuarios.crear');
        Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:usuarios.editar');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:usuarios.eliminar');

        Route::middleware('permission:roles.gestionar')->group(function () {
            Route::get('/roles', [RoleController::class, 'index']);
            Route::post('/roles', [RoleController::class, 'store']);
            Route::get('/roles/{role}', [RoleController::class, 'show']);
            Route::put('/roles/{role}', [RoleController::class, 'update']);
            Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
        });

        Route::middleware('permission:permisos.gestionar')->group(function () {
            Route::get('/permissions', [PermissionController::class, 'index']);
            Route::post('/permissions', [PermissionController::class, 'store']);
            Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy']);
        });

        Route::middleware('permission:brigadas.gestionar')->group(function () {
            Route::get('/brigadas', [BrigadaController::class, 'index']);
            Route::post('/brigadas', [BrigadaController::class, 'store']);
            Route::get('/brigadas/{brigada}', [BrigadaController::class, 'show']);
            Route::put('/brigadas/{brigada}', [BrigadaController::class, 'update']);
            Route::delete('/brigadas/{brigada}', [BrigadaController::class, 'destroy']);

            Route::get('/especialidades', [EspecialidadController::class, 'index']);
            Route::post('/especialidades', [EspecialidadController::class, 'store']);
            Route::delete('/especialidades/{especialidad}', [EspecialidadController::class, 'destroy']);
        });

        Route::middleware('permission:pacientes.gestionar')->group(function () {
            Route::get('/pacientes', [PacienteController::class, 'index']);
            Route::post('/pacientes', [PacienteController::class, 'store']);
            Route::get('/pacientes/{paciente}', [PacienteController::class, 'show']);
            Route::put('/pacientes/{paciente}', [PacienteController::class, 'update']);
        });

        Route::middleware('permission:turnos.gestionar')->group(function () {
            Route::get('/brigadas/{brigada}/asistencias', [BrigadaAsistenciaController::class, 'index']);
            Route::get('/turnos', [TurnoController::class, 'index']);
            Route::post('/turnos', [TurnoController::class, 'store']);
            Route::put('/turnos/{turno}', [TurnoController::class, 'update']);
            Route::put('/turnos/{turno}/signos-vitales', [ClinicalRecordController::class, 'storeSignosVitales']);
            Route::put('/turnos/{turno}/atencion', [ClinicalRecordController::class, 'storeAtencion']);
        });

        Route::middleware('permission:medicos.gestionar')->group(function () {
            Route::get('/medicos', [MedicoController::class, 'index']);
            Route::post('/medicos', [MedicoController::class, 'store']);
            Route::get('/medicos/{medico}', [MedicoController::class, 'show']);
            Route::put('/medicos/{medico}', [MedicoController::class, 'update']);
            Route::delete('/medicos/{medico}', [MedicoController::class, 'destroy']);

            Route::get('/brigadas/{brigada}/medicos', [BrigadaController::class, 'medicos']);
            Route::post('/brigadas/{brigada}/medicos', [BrigadaController::class, 'asignarMedicos']);
        });

        Route::middleware('permission:brigadistas.gestionar')->group(function () {
            Route::get('/brigadistas', [UserController::class, 'brigadistas']);
            Route::get('/brigadas/{brigada}/brigadistas', [BrigadaController::class, 'brigadistas']);
            Route::post('/brigadas/{brigada}/brigadistas', [BrigadaController::class, 'asignarBrigadista']);
            Route::put('/brigadas/{brigada}/brigadistas/{user}', [BrigadaController::class, 'actualizarAsistenciaBrigadista']);
            Route::delete('/brigadas/{brigada}/brigadistas/{user}', [BrigadaController::class, 'quitarBrigadista']);
        });

        Route::middleware('permission:reportes.ver')->group(function () {
            Route::get('/reportes/resumen', [ReporteController::class, 'resumen']);
            Route::get('/reportes/por-sector', [ReporteController::class, 'porSector']);
        });

        Route::middleware('permission:comunidades.gestionar')->group(function () {
            Route::get('/comunidades', [ComunidadController::class, 'index']);
            Route::post('/comunidades', [ComunidadController::class, 'store']);
            Route::get('/comunidades/{comunidad}', [ComunidadController::class, 'show']);
            Route::put('/comunidades/{comunidad}', [ComunidadController::class, 'update']);
            Route::delete('/comunidades/{comunidad}', [ComunidadController::class, 'destroy']);
        });

        Route::middleware('permission:noticias.gestionar')->group(function () {
            Route::get('/noticias', [NoticiaController::class, 'index']);
            Route::post('/noticias', [NoticiaController::class, 'store']);
            Route::get('/noticias/{noticia}', [NoticiaController::class, 'show']);
            Route::put('/noticias/{noticia}', [NoticiaController::class, 'update']);
            Route::delete('/noticias/{noticia}', [NoticiaController::class, 'destroy']);
        });

        // Revisar/gestionar solicitudes es parte de "administrar campañas" (Coordinador),
        // por eso reutiliza brigadas.gestionar en vez de un permiso nuevo.
        Route::middleware('permission:brigadas.gestionar')->group(function () {
            Route::get('/solicitudes-brigada', [SolicitudBrigadaController::class, 'index']);
            Route::put('/solicitudes-brigada/{solicitud}', [SolicitudBrigadaController::class, 'update']);
        });
    });
});
