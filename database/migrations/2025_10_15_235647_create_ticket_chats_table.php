<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ticket_id'); // Cambiado a unsignedInteger para coincidir con TicketID
            $table->string('mensaje');
            $table->string('remitente'); // 'usuario' o 'soporte'
            $table->string('correo_remitente')->nullable();
            $table->string('nombre_remitente')->nullable();
            $table->text('contenido_correo')->nullable(); // Para almacenar el HTML del correo
            $table->string('message_id')->nullable(); // ID del mensaje de correo
            $table->string('thread_id')->nullable(); // ID del hilo de conversación
            $table->json('adjuntos')->nullable(); // Adjuntos del correo
            $table->boolean('es_correo')->default(false); // Si viene de correo o es interno
            $table->boolean('leido')->default(false);
            $table->timestamps();

            // Crear índice primero
            $table->index(['ticket_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticket_chats');
    }
}
