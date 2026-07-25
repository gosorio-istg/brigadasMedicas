<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('noticias', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('resumen');
            $table->text('contenido');
            $table->string('imagen_url')->nullable();
            $table->date('fecha_publicacion');
            $table->foreignId('autor_id')->constrained('users')->cascadeOnDelete();
            // Permite guardar un borrador (publicada = false) antes de mostrarlo en la app/web.
            $table->boolean('publicada')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('noticias');
    }
};
