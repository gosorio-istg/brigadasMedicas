<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Esta columna ya estaba en User::$fillable y se usa en UserResource/UserController,
            // pero nunca tuvo su propia migración: la tabla real seguía sin la columna.
            $table->string('apellido', 150)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('apellido');
        });
    }
};
