<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Panel del Coordinador
|--------------------------------------------------------------------------
|
| Estas rutas solo devuelven el HTML/Blade de cada pantalla. Los datos se
| cargan en el navegador vía JS (public/js/api.js) contra /api/v1, el mismo
| backend que usará la app Android. No hay sesión de Laravel de por medio:
| el "login" del panel es simplemente guardar el token Bearer en localStorage,
| por eso estas rutas no llevan middleware "auth" — el guard vive en el
| cliente (requireAuth() en cada vista de layouts.app redirige a /login si
| no hay token guardado).
|
*/

Route::redirect('/', '/dashboard');

Route::view('/login', 'auth.login')->name('login');
Route::view('/recuperar-password', 'auth.recuperar-password')->name('password.request');
Route::view('/seleccion-rol', 'auth.seleccion-rol')->name('seleccion-rol');

Route::view('/dashboard', 'app.dashboard')->name('dashboard');
Route::view('/mis-campanas', 'app.mis-campanas.index')->name('mis-campanas.index');

Route::view('/brigadas', 'app.brigadas.index')->name('brigadas.index');
Route::view('/brigadas/nueva', 'app.brigadas.crear')->name('brigadas.crear');
Route::view('/brigadas/{id}', 'app.brigadas.detalle')->name('brigadas.detalle');
Route::view('/solicitudes-brigada', 'app.solicitudes-brigada.index')->name('solicitudes-brigada.index');

Route::view('/pacientes', 'app.pacientes.index')->name('pacientes.index');

Route::view('/medicos', 'app.medicos.index')->name('medicos.index');
Route::view('/medicos/nuevo', 'app.medicos.form')->name('medicos.crear');
Route::view('/medicos/{id}/editar', 'app.medicos.form')->name('medicos.editar');

Route::view('/brigadistas', 'app.brigadistas.index')->name('brigadistas.index');

Route::view('/comunidades', 'app.comunidades.index')->name('comunidades.index');

Route::view('/reportes', 'app.reportes.index')->name('reportes.index');

Route::view('/noticias', 'app.noticias.index')->name('noticias.index');
Route::view('/noticias/nueva', 'app.noticias.form')->name('noticias.crear');
Route::view('/noticias/{id}/editar', 'app.noticias.form')->name('noticias.editar');

Route::view('/usuarios', 'app.usuarios.index')->name('usuarios.index');
Route::view('/usuarios/nuevo', 'app.usuarios.form')->name('usuarios.crear');
Route::view('/usuarios/{id}/editar', 'app.usuarios.form')->name('usuarios.editar');

Route::view('/configuracion', 'app.configuracion.index')->name('configuracion.index');
