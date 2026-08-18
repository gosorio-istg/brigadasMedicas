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
        // Antes solo había texto libre (diagnóstico/receta/observaciones); estos tres campos
        // son catálogo fijo para poder tabular/reportar atenciones por motivo, tipo y cuántas
        // requirieron referencia, sin depender de parsear texto libre.
        Schema::table('atenciones', function (Blueprint $table) {
            $table->enum('motivo_consulta', [
                'enfermedad_comun', 'control', 'chequeo_preventivo', 'urgencia', 'seguimiento', 'otro',
            ])->default('enfermedad_comun')->after('diagnostico');
            $table->enum('tipo_atencion', ['primera_vez', 'seguimiento'])->default('primera_vez')->after('motivo_consulta');
            $table->boolean('requiere_referencia')->default(false)->after('tipo_atencion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atenciones', function (Blueprint $table) {
            $table->dropColumn(['motivo_consulta', 'tipo_atencion', 'requiere_referencia']);
        });
    }
};
