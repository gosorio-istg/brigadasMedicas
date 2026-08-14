<?php

namespace App\Services;

use App\Models\SyncOutbox;

class SyncOutboxService
{
    public function queue(string $collection, string|int $documentId, array $payload): SyncOutbox
    {
        return SyncOutbox::create([
            'collection' => $collection,
            'document_id' => (string) $documentId,
            'operation' => 'set',
            'payload' => $payload + ['source' => 'mysql'],
        ]);
    }
}
