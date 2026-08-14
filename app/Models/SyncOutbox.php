<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SyncOutbox extends Model
{
    use HasUuids;

    protected $table = 'sync_outbox';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['collection', 'document_id', 'operation', 'payload', 'attempts', 'last_error', 'processed_at'];

    protected $casts = ['payload' => 'array', 'processed_at' => 'datetime'];
}
