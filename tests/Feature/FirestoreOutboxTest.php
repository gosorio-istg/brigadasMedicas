<?php

namespace Tests\Feature;

use App\Services\FirestoreRestClient;
use App\Services\SyncOutboxService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class FirestoreOutboxTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_publishes_non_identifying_turn_state_and_marks_event(): void
    {
        $event = app(SyncOutboxService::class)->queue('turnos_realtime', 55, [
            'turno_id' => 55, 'numero_turno' => 'MG-001', 'estado' => 'pendiente',
            'brigada_id' => 2, 'especialidad_id' => 3,
        ]);
        $client = Mockery::mock(FirestoreRestClient::class);
        $client->shouldReceive('setDocument')->once()->withArgs(function ($collection, $document, $payload) use ($event) {
            return $collection === 'turnos_realtime' && $document === '55'
                && $payload['event_id'] === $event->id
                && ! array_key_exists('cedula', $payload)
                && ! array_key_exists('nombre', $payload)
                && ! array_key_exists('diagnostico', $payload);
        });
        $this->app->instance(FirestoreRestClient::class, $client);

        $this->artisan('sync:firestore')->assertSuccessful();
        $this->assertNotNull($event->fresh()->processed_at);
    }

    public function test_failed_publication_is_retained_for_retry_and_returns_failure(): void
    {
        $event = app(SyncOutboxService::class)->queue('turnos_realtime', 99, ['turno_id' => 99, 'estado' => 'pendiente']);
        $client = Mockery::mock(FirestoreRestClient::class);
        $client->shouldReceive('setDocument')->once()->andThrow(new RuntimeException('credential unavailable'));
        $this->app->instance(FirestoreRestClient::class, $client);

        $this->artisan('sync:firestore')->assertFailed();
        $event->refresh();
        $this->assertNull($event->processed_at);
        $this->assertSame(1, $event->attempts);
        $this->assertStringContainsString('credential unavailable', $event->last_error);
    }
}
