<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo independiente por ahora (sin FK desde Brigada/Paciente): ver BrigadaSalud_Contexto_Proyecto.md
        // sección 6.5 sobre la decisión pendiente de migrar "ubicacion"/"sector" a comunidad_id más adelante.
        Schema::create('comunidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('sector');
            $table->string('referencia_ubicacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunidades');
    }
};
