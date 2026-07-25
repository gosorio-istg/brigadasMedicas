<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brigada_id')->constrained('brigadas')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->cascadeOnDelete();
            // Sin llave foránea todavía: la tabla "medicos" se crea en el módulo Médicos (prioridad 2).
            // Se agrega la restricción FK en la migración de ese módulo cuando la tabla ya exista.
            $table->unsignedBigInteger('medico_id')->nullable();
            $table->string('numero_turno');
            $table->enum('estado', ['pendiente', 'en_espera', 'atendido', 'cancelado'])->default('pendiente');
            // Qué brigadista registró el turno (permite el flujo de "registro asistido").
            $table->foreignId('registrado_por')->constrained('users')->cascadeOnDelete();
            $table->timestamp('hora_registro')->useCurrent();
            $table->timestamp('hora_atencion')->nullable();
            $table->timestamps();

            // Índice usado para generar el número de turno consecutivo por brigada+especialidad.
            $table->index(['brigada_id', 'especialidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos');
    }
};
