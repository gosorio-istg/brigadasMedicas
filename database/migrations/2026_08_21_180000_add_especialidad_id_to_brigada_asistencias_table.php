<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brigada_asistencias', function (Blueprint $table) {
            // Representa una preinscripción de interés, no un turno médico. Se deja
            // nullable porque "Tal vez" y "No asistiré" no reservan especialidad.
            $table->foreignId('especialidad_id')
                ->nullable()
                ->after('user_id')
                ->constrained('especialidades')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('brigada_asistencias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('especialidad_id');
        });
    }
};
