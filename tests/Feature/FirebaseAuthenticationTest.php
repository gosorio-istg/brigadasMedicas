<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FirebaseAuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.firebase.web_api_key' => 'test-api-key']);
        Role::firstOrCreate(['name' => 'Ciudadano', 'guard_name' => 'web']);
    }

    public function test_it_creates_a_citizen_and_returns_a_sanctum_token(): void
    {
        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response([
                'users' => [[
                    'localId' => 'firebase-test-uid',
                    'email' => 'firebase-test@example.test',
                    'displayName' => 'Persona Firebase',
                ]],
            ]),
        ]);

        $response = $this->postJson('/api/v1/auth/firebase', [
            'id_token' => 'valid-token',
            'cedula' => '0912345675',
            'nombres' => 'Persona',
            'apellidos' => 'Prueba',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.firebase_uid', 'firebase-test-uid')
            ->assertJsonPath('user.firebase_linked', true)
            ->assertJsonFragment(['Ciudadano'])
            ->assertJsonStructure(['token', 'token_type', 'user' => ['roles', 'permissions']]);

        $this->assertDatabaseHas('users', [
            'email' => 'firebase-test@example.test',
            'firebase_uid' => 'firebase-test-uid',
        ]);
    }

    public function test_it_rejects_an_invalid_firebase_token(): void
    {
        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response(['error' => ['message' => 'INVALID_ID_TOKEN']], 400),
        ]);

        $this->postJson('/api/v1/auth/firebase', ['id_token' => 'invalid-token'])
            ->assertUnauthorized();
    }

    public function test_it_links_an_existing_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@example.test',
            'firebase_uid' => null,
            'activo' => true,
        ]);

        Http::fake([
            'identitytoolkit.googleapis.com/*' => Http::response([
                'users' => [['localId' => 'existing-firebase-uid', 'email' => 'existing@example.test']],
            ]),
        ]);

        $this->postJson('/api/v1/auth/firebase', ['id_token' => 'valid-token'])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'firebase_uid' => 'existing-firebase-uid',
        ]);
    }
}
