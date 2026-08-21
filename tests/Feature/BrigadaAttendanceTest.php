<?php

namespace Tests\Feature;

use App\Models\Brigada;
use App\Models\Especialidad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BrigadaAttendanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_an_authenticated_citizen_can_create_and_change_their_attendance(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $coordinator = User::factory()->create(['activo' => true]);
        $brigada = Brigada::create([
            'nombre' => 'Brigada asistencia '.uniqid(), 'fecha' => now()->addDay(),
            'ubicacion' => 'Sector prueba', 'estado' => 'programada', 'coordinador_id' => $coordinator->id,
        ]);
        $especialidad = Especialidad::firstOrCreate(
            ['nombre' => 'Medicina de preinscripción '.uniqid()],
            ['activa' => true]
        );
        $brigada->especialidades()->attach($especialidad->id, ['cupos' => 20]);
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/brigadas/{$brigada->id}/mi-asistencia", [
            'estado' => 'asistira',
            'especialidad_id' => $especialidad->id,
        ])->assertOk()
            ->assertJsonPath('data.estado', 'asistira')
            ->assertJsonPath('data.especialidad.nombre', $especialidad->nombre);

        $campaignsResponse = $this->getJson('/api/v1/me/campanas')->assertOk();
        $campaign = collect($campaignsResponse->json('data'))->firstWhere('id', $brigada->id);
        $this->assertSame($especialidad->id, $campaign['mi_especialidad']['id'] ?? null);

        $this->putJson("/api/v1/brigadas/{$brigada->id}/mi-asistencia", ['estado' => 'tal_vez'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'tal_vez')
            ->assertJsonPath('data.especialidad', null);

        $this->assertDatabaseCount('brigada_asistencias', 1);
        $this->assertDatabaseHas('brigada_asistencias', [
            'brigada_id' => $brigada->id,
            'user_id' => $user->id,
            'estado' => 'tal_vez',
            'especialidad_id' => null,
        ]);
        $this->getJson("/api/v1/brigadas/{$brigada->id}/mi-asistencia")
            ->assertOk()->assertJsonPath('data.estado', 'tal_vez');

        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'turnos.gestionar', 'guard_name' => 'web']));
        $this->getJson("/api/v1/brigadas/{$brigada->id}/asistencias")
            ->assertOk()
            ->assertJsonPath('data.0.nombre', $user->name)
            ->assertJsonPath('data.0.especialidad', null);
    }

    public function test_attendance_requires_authentication_and_a_valid_status(): void
    {
        $coordinator = User::factory()->create(['activo' => true]);
        $brigada = Brigada::create([
            'nombre' => 'Brigada auth '.uniqid(), 'fecha' => now()->addDay(),
            'ubicacion' => 'Sector prueba', 'estado' => 'programada', 'coordinador_id' => $coordinator->id,
        ]);
        $this->putJson("/api/v1/brigadas/{$brigada->id}/mi-asistencia", ['estado' => 'asistira'])->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create(['activo' => true]));
        $this->putJson("/api/v1/brigadas/{$brigada->id}/mi-asistencia", ['estado' => 'quizas'])->assertUnprocessable();
        $this->putJson("/api/v1/brigadas/{$brigada->id}/mi-asistencia", ['estado' => 'asistira'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('especialidad_id');

        $especialidadNoOfrecida = Especialidad::firstOrCreate(
            ['nombre' => 'Especialidad no ofrecida '.uniqid()],
            ['activa' => true]
        );
        $this->putJson("/api/v1/brigadas/{$brigada->id}/mi-asistencia", [
            'estado' => 'asistira',
            'especialidad_id' => $especialidadNoOfrecida->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('especialidad_id');
    }
}
