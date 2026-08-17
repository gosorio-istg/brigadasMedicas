<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // /reportes/resumen y /turnos filtran/agrupan por estado en cada consulta;
        // sin índice esto es un full table scan que empeora conforme crece turnos.
        Schema::table('turnos', function (Blueprint $table) {
            $table->index(['brigada_id', 'estado']);
        });

        // /reportes/por-sector agrupa por pacientes.sector en cada consulta.
        Schema::table('pacientes', function (Blueprint $table) {
            $table->index('sector');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropIndex(['brigada_id', 'estado']);
        });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropIndex(['sector']);
        });
    }
};
