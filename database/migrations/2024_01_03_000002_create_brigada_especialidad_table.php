// database/migrations/2024_01_03_000002_create_brigada_especialidad_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brigada_especialidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brigada_id')->constrained('brigadas')->cascadeOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->cascadeOnDelete();
            $table->unsignedInteger('cupos')->default(0);
            $table->timestamps();

            $table->unique(['brigada_id', 'especialidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brigada_especialidad');
    }
};
