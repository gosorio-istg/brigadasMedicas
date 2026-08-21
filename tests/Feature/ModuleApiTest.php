<?php

namespace Tests\Feature;

use App\Models\Brigada;
use App\Models\ConfiguracionSistema;
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

    public function test_android_download_configuration_is_public_but_only_an_admin_can_update_it(): void
    {
        ConfiguracionSistema::query()->delete();

        // Una instalación nueva publica por defecto la APK incluida en la plataforma.
        $this->getJson('/api/v1/public/configuracion')
            ->assertOk()
            ->assertJsonPath('data.apk_android_url', ConfiguracionSistema::RUTA_APK_ANDROID_LOCAL)
            ->assertJsonPath('data.apk_android_download_url', url(ConfiguracionSistema::RUTA_APK_ANDROID_LOCAL));

        $sinPermiso = User::factory()->create(['activo' => true]);
        Sanctum::actingAs($sinPermiso);
        $this->putJson('/api/v1/configuracion-sistema', [
            'apk_android_url' => 'https://descargas.example.com/brigadasalud.apk',
        ])->assertForbidden();

        $administrador = User::factory()->create(['activo' => true]);
        $administrador->givePermissionTo(Permission::firstOrCreate([
            'name' => 'configuracion.gestionar',
            'guard_name' => 'web',
        ]));
        Sanctum::actingAs($administrador);

        $this->putJson('/api/v1/configuracion-sistema', [
            'apk_android_url' => 'http://descargas.example.com/brigadasalud.apk',
        ])->assertUnprocessable();

        $this->putJson('/api/v1/configuracion-sistema', [
            'apk_android_url' => ConfiguracionSistema::RUTA_APK_ANDROID_LOCAL,
        ])->assertOk()
            ->assertJsonPath('data.apk_android_url', ConfiguracionSistema::RUTA_APK_ANDROID_LOCAL);

        $this->putJson('/api/v1/configuracion-sistema', [
            'apk_android_url' => 'https://descargas.example.com/brigadasalud.apk',
        ])->assertOk()
            ->assertJsonPath('data.apk_android_url', 'https://descargas.example.com/brigadasalud.apk');

        $publica = $this->getJson('/api/v1/public/configuracion')
            ->assertOk()
            ->assertJsonPath('data.apk_android_url', 'https://descargas.example.com/brigadasalud.apk');
        $this->assertNotEmpty($publica->json('data.qr_android_url'));

        $qr = $this->get('/api/v1/public/configuracion/android/qr')->assertOk();
        $qr->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');
        $this->assertStringContainsString('<svg', $qr->getContent());

        $this->get(route('android.apk.download', absolute: false))
            ->assertOk()
            ->assertDownload('BrigadasMedicasV3.apk');
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

    public function test_doctor_can_view_their_assigned_campaign_but_not_an_unrelated_one(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Medico');
        $especialidad = Especialidad::create(['nombre' => 'Campaña '.uniqid(), 'activa' => true]);
        $medico = Medico::create(['user_id' => $user->id, 'nombres' => $user->name, 'credencial_cmp' => 'CMP-'.random_int(1000, 9999), 'especialidad_id' => $especialidad->id, 'disponible' => true]);

        $propia = Brigada::create(['nombre' => 'Brigada propia '.uniqid(), 'fecha' => now()->addDay(), 'ubicacion' => 'Sector propio', 'estado' => 'programada', 'coordinador_id' => $user->id]);
        $propia->medicos()->attach($medico->id);

        $ajena = Brigada::create(['nombre' => 'Brigada ajena '.uniqid(), 'fecha' => now()->addDay(), 'ubicacion' => 'Sector ajeno', 'estado' => 'programada', 'coordinador_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/brigadas/{$propia->id}")->assertOk();
        $this->getJson("/api/v1/brigadas/{$propia->id}/medicos")->assertOk();
        $this->getJson("/api/v1/brigadas/{$ajena->id}")->assertForbidden();
    }
}
