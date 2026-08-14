<?php

namespace Tests\Feature;

use App\Models\Brigada;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ClinicalWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_an_authorized_user_can_record_vitals_and_finish_an_attention(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'turnos.gestionar', 'guard_name' => 'web']);
        $user = User::factory()->create(['activo' => true]);
        $user->givePermissionTo($permission);
        Sanctum::actingAs($user);

        $especialidad = Especialidad::create(['nombre' => 'Prueba Clínica '.uniqid(), 'activa' => true]);
        $brigada = Brigada::create([
            'nombre' => 'Brigada prueba '.uniqid(), 'fecha' => now()->addDay(),
            'ubicacion' => 'Sector de pruebas', 'estado' => 'programada', 'coordinador_id' => $user->id,
        ]);
        $brigada->especialidades()->attach($especialidad->id, ['cupos' => 10]);
        $paciente = Paciente::create([
            'cedula' => '0999999999', 'nombres' => 'Paciente', 'apellidos' => 'Prueba',
            'fecha_nacimiento' => '1990-01-01', 'sexo' => 'otro',
        ]);
        $turno = Turno::create([
            'brigada_id' => $brigada->id, 'paciente_id' => $paciente->id,
            'especialidad_id' => $especialidad->id, 'numero_turno' => 'PR-001',
            'estado' => 'pendiente', 'registrado_por' => $user->id, 'hora_registro' => now(),
        ]);

        $this->putJson("/api/v1/turnos/{$turno->id}/signos-vitales", [
            'presion_arterial' => '120/80', 'temperatura' => 36.7,
            'frecuencia_cardiaca' => 78, 'frecuencia_respiratoria' => 18,
        ])->assertCreated()->assertJsonPath('data.turno_id', $turno->id);

        $this->assertDatabaseHas('signos_vitales', ['turno_id' => $turno->id, 'presion_arterial' => '120/80']);
        $this->assertDatabaseHas('sync_outbox', ['collection' => 'turnos_realtime', 'document_id' => (string) $turno->id]);
        $this->assertSame('en_espera', $turno->fresh()->estado);

        $this->putJson("/api/v1/turnos/{$turno->id}/atencion", [
            'diagnostico' => 'Diagnóstico de prueba', 'receta' => 'Tratamiento de prueba',
            'observaciones' => 'Control en siete días',
        ])->assertCreated()->assertJsonPath('data.turno_id', $turno->id);

        $this->assertDatabaseHas('atenciones', ['turno_id' => $turno->id, 'diagnostico' => 'Diagnóstico de prueba']);
        $this->assertSame('atendido', $turno->fresh()->estado);
        $this->assertNotNull($turno->fresh()->hora_atencion);
    }
}
