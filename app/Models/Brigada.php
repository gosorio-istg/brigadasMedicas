<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brigada extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion', 'fecha', 'ubicacion', 'estado', 'coordinador_id'];

    // Laravel 10 no soporta el método casts(): array (eso llegó en Laravel 11); debe ser esta propiedad.
    protected $casts = ['fecha' => 'date'];

    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinador_id');
    }

    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(Especialidad::class, 'brigada_especialidad')
            ->withPivot('cupos')
            ->withTimestamps();
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(Turno::class);
    }

    public function medicos(): BelongsToMany
    {
        return $this->belongsToMany(Medico::class, 'brigada_medico')->withTimestamps();
    }

    // "Brigadista" no es un modelo aparte: es un User con rol Brigadista asignado a esta brigada.
    public function brigadistas(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'brigada_brigadista')
            ->withPivot(['rol_equipo', 'asistio'])
            ->withTimestamps();
    }
}
