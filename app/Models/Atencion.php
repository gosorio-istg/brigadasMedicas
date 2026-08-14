<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Atencion extends Model
{
    protected $table = 'atenciones';

    protected $fillable = [
        'turno_id', 'medico_id', 'diagnostico', 'receta', 'observaciones',
        'registrado_por', 'fecha_atencion',
    ];

    protected $casts = ['fecha_atencion' => 'datetime'];

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class);
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
