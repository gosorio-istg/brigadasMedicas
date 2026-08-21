<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paciente extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'cedula', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo', 'telefono', 'sector'];

    // Laravel 10 no soporta el método casts(): array (eso llegó en Laravel 11); debe ser esta propiedad.
    protected $casts = ['fecha_nacimiento' => 'date'];

    public function turnos(): HasMany
    {
        return $this->hasMany(Turno::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
