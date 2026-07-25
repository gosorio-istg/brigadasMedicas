<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Especialidad extends Model
{
    use HasFactory;

    // Se declara explícitamente: el pluralizador de Eloquent (en inglés) generaría "especialidads".
    protected $table = 'especialidades';

    protected $fillable = ['nombre', 'activa'];

    // Laravel 10 no soporta el método casts(): array (eso llegó en Laravel 11); debe ser esta propiedad.
    protected $casts = ['activa' => 'boolean'];

    public function brigadas(): BelongsToMany
    {
        return $this->belongsToMany(Brigada::class, 'brigada_especialidad')
            ->withPivot('cupos')
            ->withTimestamps();
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(Turno::class);
    }
}
