<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            // Identificador principal del paciente (no se usa QR): cédula + nombres.
            $table->string('cedula', 10)->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['masculino', 'femenino', 'otro']);
            $table->string('telefono')->nullable();
            // Texto libre por ahora; cuando exista el módulo Comunidades se migra a comunidad_id (FK).
            $table->string('sector')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
