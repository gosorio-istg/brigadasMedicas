<?php

namespace Tests\Feature;

use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModuleApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_module_routes_enforce_authentication_and_permissions(): void
    {
        $this->getJson('/api/v1/comunidades')->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create(['activo' => true]));
        $this->getJson('/api/v1/comunidades')->assertForbidden();
    }

    public function test_authorized_user_can_manage_communities(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'comunidades.gestionar', 'guard_name' => 'web']));
        Sanctum::actingAs($user);

        $created = $this->postJson('/api/v1/comunidades', [
            'nombre' => 'Comunidad móvil '.uniqid(), 'sector' => 'Sector norte', 'referencia_ubicacion' => 'Junto al parque',
        ])->assertCreated()->json('data');

        $this->putJson("/api/v1/comunidades/{$created['id']}", ['sector' => 'Sector sur'])
            ->assertOk()->assertJsonPath('data.sector', 'Sector sur');
        $this->deleteJson("/api/v1/comunidades/{$created['id']}")->assertOk();
        $this->assertDatabaseMissing('comunidades', ['id' => $created['id']]);
    }

    public function test_user_can_update_only_their_supported_profile_fields(): void
    {
        $user = User::factory()->create(['activo' => true, 'apellido' => 'Inicial']);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/me', [
            'name' => 'Nombre actualizado', 'apellido' => 'Apellido actualizado',
            'telefono' => '0991234567', 'sector' => 'Sector Central',
            'activo' => false, 'roles' => ['Administrador'],
        ])->assertOk()
            ->assertJsonPath('data.telefono', '0991234567')
            ->assertJsonPath('data.sector', 'Sector Central');

        $user->refresh();
        $this->assertTrue($user->activo);
        $this->assertFalse($user->hasRole('Administrador'));
    }

    public function test_linked_doctor_can_read_and_update_their_availability(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $especialidad = Especialidad::create(['nombre' => 'Disponibilidad '.uniqid(), 'activa' => true]);
        Medico::create(['user_id' => $user->id, 'nombres' => $user->name, 'credencial_cmp' => 'CMP-'.random_int(1000, 9999), 'especialidad_id' => $especialidad->id, 'disponible' => false]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/disponibilidad-medica')->assertOk()->assertJsonPath('data.disponible', false);
        $this->putJson('/api/v1/me/disponibilidad-medica', ['disponible' => true])->assertOk()->assertJsonPath('data.disponible', true);
        $this->assertDatabaseHas('medicos', ['user_id' => $user->id, 'disponible' => true]);
    }
}
