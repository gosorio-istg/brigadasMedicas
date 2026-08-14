<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrigadaAsistencia extends Model
{
    protected $fillable = ['brigada_id', 'user_id', 'estado'];

    public function brigada(): BelongsTo
    {
        return $this->belongsTo(Brigada::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
