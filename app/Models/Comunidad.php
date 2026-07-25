<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comunidad extends Model
{
    use HasFactory;

    // Se declara explícitamente: el pluralizador de Eloquent (en inglés) generaría "comunidads".
    protected $table = 'comunidades';

    protected $fillable = ['nombre', 'sector', 'referencia_ubicacion'];
}
