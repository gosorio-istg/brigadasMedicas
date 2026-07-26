<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable porque los usuarios ya existentes no la tienen todavía; el login por
            // cédula solo funciona para cuentas que la hayan registrado (ver AuthController::login).
            $table->string('cedula', 10)->nullable()->unique()->after('apellido');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('cedula');
        });
    }
};
