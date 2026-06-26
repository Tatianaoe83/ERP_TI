<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimiento_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mantenimiento_id');
            $table->text('mensaje');
            $table->string('remitente');
            $table->string('correo_remitente')->nullable();
            $table->string('nombre_remitente')->nullable();
            $table->text('contenido_correo')->nullable();
            $table->string('message_id')->nullable();
            $table->string('thread_id')->nullable();
            $table->json('adjuntos')->nullable();
            $table->boolean('es_correo')->default(false);
            $table->boolean('leido')->default(false);
            $table->unsignedInteger('notificaciones_pendientes')->default(0);
            $table->timestamps();

            $table->index(['mantenimiento_id', 'created_at']);
            $table->index('message_id');
            $table->index('thread_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_chats');
    }
};
