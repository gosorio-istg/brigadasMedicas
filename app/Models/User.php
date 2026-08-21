<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'apellido',
        'cedula',
        'email',
        'firebase_uid',
        'fecha_nacimiento',
        'telefono',
        'sector',
        'password',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Laravel 10 no soporta el método casts(): array (eso llegó en Laravel 11); debe ser esta propiedad.
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
        'fecha_nacimiento' => 'date',
    ];

    // Brigadas en las que este usuario (brigadista) está asignado como parte del equipo.
    public function brigadas(): BelongsToMany
    {
        return $this->belongsToMany(Brigada::class, 'brigada_brigadista')
            ->withPivot(['rol_equipo', 'asistio'])
            ->withTimestamps();
    }

    public function preferencia(): HasOne
    {
        return $this->hasOne(Preferencia::class);
    }

    // Un ciudadano puede tener una cuenta sin historia clínica hasta que recibe
    // su primer turno. El vínculo permite mostrar ese turno nuevamente en la app.
    public function paciente(): HasOne
    {
        return $this->hasOne(Paciente::class);
    }
}
