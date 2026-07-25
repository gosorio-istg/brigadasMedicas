<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            // Credencial profesional (registro del colegio médico) que identifica de forma única al médico.
            $table->string('credencial_cmp')->unique();
            $table->foreignId('especialidad_id')->constrained('especialidades')->cascadeOnDelete();
            $table->string('telefono')->nullable();
            $table->boolean('disponible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};
