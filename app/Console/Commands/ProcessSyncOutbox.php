<?php

namespace App\Console\Commands;

use App\Models\SyncOutbox;
use App\Services\FirestoreRestClient;
use Illuminate\Console\Command;

class ProcessSyncOutbox extends Command
{
    protected $signature = 'sync:firestore {--limit=100}';

    protected $description = 'Publica en Firestore los eventos confirmados en MySQL';

    public function handle(FirestoreRestClient $firestore): int
    {
        $failures = 0;
        $events = SyncOutbox::whereNull('processed_at')->orderBy('created_at')->limit((int) $this->option('limit'))->get();
        foreach ($events as $event) {
            try {
                $firestore->setDocument($event->collection, $event->document_id, $event->payload + ['event_id' => $event->id]);
                $event->update(['processed_at' => now(), 'last_error' => null]);
            } catch (\Throwable $error) {
                $failures++;
                $event->increment('attempts');
                $event->update(['last_error' => mb_substr($error->getMessage(), 0, 65000)]);
                $this->error("{$event->id}: {$error->getMessage()}");
            }
        }
        $this->info("Procesados: {$events->whereNotNull('processed_at')->count()} de {$events->count()}");

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
