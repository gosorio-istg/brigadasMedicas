<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Turno extends Model
{
    use HasFactory;

    protected $fillable = [
        'brigada_id',
        'paciente_id',
        'especialidad_id',
        'medico_id',
        'numero_turno',
        'estado',
        'registrado_por',
        'hora_registro',
        'hora_atencion',
    ];

    // Laravel 10 no soporta el método casts(): array (eso llegó en Laravel 11); debe ser esta propiedad.
    protected $casts = [
        'hora_registro' => 'datetime',
        'hora_atencion' => 'datetime',
    ];

    public function brigada(): BelongsTo
    {
        return $this->belongsTo(Brigada::class);
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }

    // Brigadista (o coordinador) que registró el turno, para soportar el registro asistido.
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function signosVitales(): HasOne
    {
        return $this->hasOne(SignosVitales::class);
    }

    public function atencion(): HasOne
    {
        return $this->hasOne(Atencion::class);
    }
}
