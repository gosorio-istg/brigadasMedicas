<?php

namespace Tests\Feature;

use App\Models\Brigada;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Turno;
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

    public function test_doctor_only_sees_turns_of_their_own_specialty(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Medico');
        $especialidadPropia = Especialidad::create(['nombre' => 'Propia '.uniqid(), 'activa' => true]);
        $especialidadAjena = Especialidad::create(['nombre' => 'Ajena '.uniqid(), 'activa' => true]);
        $medico = Medico::create(['user_id' => $user->id, 'nombres' => $user->name, 'credencial_cmp' => 'CMP-'.random_int(1000, 9999), 'especialidad_id' => $especialidadPropia->id, 'disponible' => true]);

        $brigada = Brigada::create(['nombre' => 'Brigada mixta '.uniqid(), 'fecha' => now()->addDay(), 'ubicacion' => 'Sector mixto', 'estado' => 'en_curso', 'coordinador_id' => $user->id]);
        $brigada->especialidades()->attach([$especialidadPropia->id => ['cupos' => 5], $especialidadAjena->id => ['cupos' => 5]]);
        $brigada->medicos()->attach($medico->id);

        $paciente = Paciente::create(['cedula' => '0912345675', 'nombres' => 'Paciente', 'apellidos' => 'Mixto', 'fecha_nacimiento' => '1992-04-10', 'sexo' => 'otro']);
        $turnoPropio = Turno::create(['brigada_id' => $brigada->id, 'paciente_id' => $paciente->id, 'especialidad_id' => $especialidadPropia->id, 'numero_turno' => 'PRO-001', 'estado' => 'pendiente', 'registrado_por' => $user->id, 'hora_registro' => now()]);
        Turno::create(['brigada_id' => $brigada->id, 'paciente_id' => $paciente->id, 'especialidad_id' => $especialidadAjena->id, 'numero_turno' => 'AJE-001', 'estado' => 'pendiente', 'registrado_por' => $user->id, 'hora_registro' => now()]);

        Sanctum::actingAs($user);

        // Sin filtro explícito, y también pidiendo la cola completa de la brigada (como hace
        // el detalle de campaña en la app): en ambos casos solo debe ver su propia especialidad.
        $this->getJson('/api/v1/turnos')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $turnoPropio->id);
        $this->getJson("/api/v1/turnos?brigada_id={$brigada->id}")->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $turnoPropio->id);
    }
}
