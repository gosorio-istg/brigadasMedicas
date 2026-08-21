<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            // Vincula la cuenta del ciudadano con su historia clínica sin obligar a que
            // los pacientes registrados de forma asistida tengan una cuenta en la app.
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Recupera automáticamente vínculos históricos cuando ambas tablas ya tienen
        // la misma cédula. Las dos columnas son únicas, por lo que no se crean ambigüedades.
        DB::statement(<<<'SQL'
            UPDATE pacientes
            INNER JOIN users ON users.cedula = pacientes.cedula
            SET pacientes.user_id = users.id
            WHERE pacientes.user_id IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
