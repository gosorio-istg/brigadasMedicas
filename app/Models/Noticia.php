<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Noticia extends Model
{
    use HasFactory;

    protected $fillable = ['titulo', 'resumen', 'contenido', 'imagen_url', 'fecha_publicacion', 'autor_id', 'publicada'];

    // Laravel 10 no soporta el método casts(): array (eso llegó en Laravel 11); debe ser esta propiedad.
    protected $casts = [
        'fecha_publicacion' => 'date',
        'publicada' => 'boolean',
    ];

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
