<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permite que un ciudadano (sin cuenta en el sistema) pida que se organice una
        // brigada en su sector. El Coordinador la revisa y, si la aprueba, la vincula
        // a una Brigada real ya creada (brigada_id).
        Schema::create('solicitudes_brigada', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_solicitante');
            $table->string('telefono_contacto');
            $table->string('sector');
            // Texto libre (ej. "Medicina General, Odontología"): permite a los coordinadores
            // conocer la demanda de especialidades antes de organizar la brigada.
            $table->string('especialidades_solicitadas')->nullable();
            $table->text('motivo')->nullable();
            $table->enum('estado', ['pendiente', 'en_revision', 'aprobada', 'rechazada'])->default('pendiente');
            $table->foreignId('brigada_id')->nullable()->constrained('brigadas')->nullOnDelete();
            $table->text('notas_coordinador')->nullable();
            $table->foreignId('gestionado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_brigada');
    }
};
