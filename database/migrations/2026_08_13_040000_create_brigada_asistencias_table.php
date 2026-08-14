<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brigada_asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brigada_id')->constrained('brigadas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('estado', 20);
            $table->timestamps();
            $table->unique(['brigada_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brigada_asistencias');
    }
};
