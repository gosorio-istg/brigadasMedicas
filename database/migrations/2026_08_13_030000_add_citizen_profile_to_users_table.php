<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('fecha_nacimiento')->nullable()->after('firebase_uid');
            $table->string('telefono', 20)->nullable()->after('fecha_nacimiento');
            $table->string('sector', 150)->nullable()->after('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['fecha_nacimiento', 'telefono', 'sector']));
    }
};
