<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_outbox', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('collection', 100);
            $table->string('document_id', 150);
            $table->string('operation', 20)->default('set');
            $table->json('payload');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['processed_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_outbox');
    }
};
