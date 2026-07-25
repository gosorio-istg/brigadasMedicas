<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Preferencia extends Model
{
    use HasFactory;

    // Se declara explícitamente: el pluralizador de Eloquent generaría "preferencias",
    // pero la tabla es "user_preferencias".
    protected $table = 'user_preferencias';

    protected $fillable = ['user_id', 'notificaciones_email', 'notificaciones_push'];

    // Laravel 10 no soporta el método casts(): array (eso llegó en Laravel 11); debe ser esta propiedad.
    protected $casts = [
        'notificaciones_email' => 'boolean',
        'notificaciones_push' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
