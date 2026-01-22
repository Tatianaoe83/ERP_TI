<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('solicitud_cotizacion_tokens', function (Blueprint $table) {
            $table->id();
            $table->integer('solicitud_id');
            $table->uuid('token')->unique();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->dateTime('used_at')->nullable();
            $table->timestamps();

            $table->foreign('solicitud_id')
                ->references('SolicitudID')
                ->on('solicitudes')
                ->cascadeOnDelete();

            $table->index(['expires_at', 'revoked_at']);
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_cotizacion_tokens');
    }
};
