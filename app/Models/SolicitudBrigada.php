<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudBrigada extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_brigada';

    protected $fillable = [
        'nombre_solicitante',
        'telefono_contacto',
        'sector',
        'especialidades_solicitadas',
        'motivo',
        'estado',
        'brigada_id',
        'notas_coordinador',
        'gestionado_por',
    ];

    public function brigada(): BelongsTo
    {
        return $this->belongsTo(Brigada::class);
    }

    // Coordinador que aprobó/rechazó la solicitud (null mientras esté pendiente).
    public function gestionadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestionado_por');
    }
}
