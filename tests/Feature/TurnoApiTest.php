<?php

namespace Tests\Feature;

use App\Models\Brigada;
use App\Models\Especialidad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TurnoApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mobile_registration_creates_patient_turn_and_realtime_event_atomically(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'turnos.gestionar', 'guard_name' => 'web']));
        Sanctum::actingAs($user);
        $especialidad = Especialidad::create(['nombre' => 'Especialidad '.uniqid(), 'activa' => true]);
        $brigada = Brigada::create(['nombre' => 'Brigada móvil '.uniqid(), 'fecha' => now()->addDay(), 'ubicacion' => 'Sector móvil', 'estado' => 'programada', 'coordinador_id' => $user->id]);
        $brigada->especialidades()->attach($especialidad->id, ['cupos' => 2]);

        $response = $this->postJson('/api/v1/turnos', [
            'brigada_id' => $brigada->id, 'especialidad_id' => $especialidad->id,
            'paciente' => ['cedula' => '0912345675', 'nombres' => 'Paciente', 'apellidos' => 'Móvil', 'fecha_nacimiento' => '1992-04-10', 'sexo' => 'otro', 'telefono' => '0991234567', 'sector' => 'Sector Móvil'],
        ])->assertCreated()->assertJsonPath('data.estado', 'pendiente');

        $turnoId = $response->json('data.id');
        $pacienteId = $response->json('data.paciente.id');
        $this->assertDatabaseHas('turnos', ['id' => $turnoId, 'paciente_id' => $pacienteId]);
        $this->assertDatabaseHas('sync_outbox', ['collection' => 'turnos_realtime', 'document_id' => (string) $turnoId]);
        $this->postJson('/api/v1/turnos', ['brigada_id' => $brigada->id, 'especialidad_id' => $especialidad->id, 'paciente_id' => $pacienteId])->assertUnprocessable();
    }
}
