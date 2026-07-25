<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot que indica qué médicos asisten a cada brigada.
        Schema::create('brigada_medico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brigada_id')->constrained('brigadas')->cascadeOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['brigada_id', 'medico_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brigada_medico');
    }
};
