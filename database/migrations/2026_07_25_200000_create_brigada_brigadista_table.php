<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un "brigadista" no es una tabla nueva: es un User con rol Brigadista. Este pivot solo
        // registra su asignación a una brigada concreta, su función en el equipo y si asistió.
        Schema::create('brigada_brigadista', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brigada_id')->constrained('brigadas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('rol_equipo', ['registro', 'apoyo_logistico', 'atencion_medica', 'coordinacion'])->default('registro');
            // null = asistencia aún no confirmada (brigada todavía no ocurre).
            $table->boolean('asistio')->nullable();
            $table->timestamps();

            $table->unique(['brigada_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brigada_brigadista');
    }
};
