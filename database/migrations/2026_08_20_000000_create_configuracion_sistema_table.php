<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Es una tabla de una sola fila. Se deja preparada para sumar más opciones
        // globales sin mezclarlas con las preferencias personales de cada usuario.
        Schema::create('configuracion_sistema', function (Blueprint $table) {
            $table->id();
            $table->text('apk_android_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_sistema');
    }
};
