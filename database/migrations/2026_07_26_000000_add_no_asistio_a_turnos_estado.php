<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Nuevo estado para marcar a un paciente cuyo turno ya pasó y no se presentó,
        // sin confundirlo con "cancelado" (que implica que el turno se retiró a propósito).
        DB::statement("ALTER TABLE turnos MODIFY estado ENUM('pendiente', 'en_espera', 'atendido', 'cancelado', 'no_asistio') NOT NULL DEFAULT 'pendiente'");
    }

    public function down(): void
    {
        DB::statement("UPDATE turnos SET estado = 'cancelado' WHERE estado = 'no_asistio'");
        DB::statement("ALTER TABLE turnos MODIFY estado ENUM('pendiente', 'en_espera', 'atendido', 'cancelado') NOT NULL DEFAULT 'pendiente'");
    }
};
