<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained('users')->nullOnDelete();
        });

        Schema::create('signos_vitales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->unique()->constrained('turnos')->cascadeOnDelete();
            $table->string('presion_arterial', 20);
            $table->decimal('temperatura', 4, 1);
            $table->unsignedSmallInteger('frecuencia_cardiaca')->nullable();
            $table->unsignedSmallInteger('frecuencia_respiratoria')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('atenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->unique()->constrained('turnos')->cascadeOnDelete();
            $table->foreignId('medico_id')->nullable()->constrained('medicos')->nullOnDelete();
            $table->text('diagnostico');
            $table->text('receta')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->cascadeOnDelete();
            $table->timestamp('fecha_atencion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atenciones');
        Schema::dropIfExists('signos_vitales');
        Schema::table('medicos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
