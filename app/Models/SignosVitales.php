<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignosVitales extends Model
{
    protected $table = 'signos_vitales';

    protected $fillable = [
        'turno_id', 'presion_arterial', 'temperatura', 'frecuencia_cardiaca',
        'frecuencia_respiratoria', 'registrado_por',
    ];

    protected $casts = ['temperatura' => 'decimal:1'];

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
