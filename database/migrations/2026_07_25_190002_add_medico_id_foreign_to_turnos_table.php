<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La tabla "medicos" ya existe (módulo Médicos): ahora sí se puede restringir turnos.medico_id.
        // nullOnDelete: si se elimina un médico, el turno conserva su historial con medico_id en null.
        Schema::table('turnos', function (Blueprint $table) {
            $table->foreign('medico_id')->references('id')->on('medicos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropForeign(['medico_id']);
        });
    }
};
