<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medico extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'nombres', 'credencial_cmp', 'especialidad_id', 'telefono', 'disponible'];

    // Laravel 10 no soporta el método casts(): array (eso llegó en Laravel 11); debe ser esta propiedad.
    protected $casts = ['disponible' => 'boolean'];

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brigadas(): BelongsToMany
    {
        return $this->belongsToMany(Brigada::class, 'brigada_medico')->withTimestamps();
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(Turno::class);
    }
}
