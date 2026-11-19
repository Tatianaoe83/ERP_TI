<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('AnioProgramacion');
            $table->unsignedInteger('EmpleadoID');
            $table->unsignedInteger('InventarioID');
            $table->string('TipoMantenimiento', 20);
            $table->string('Folio', 100)->nullable();
            $table->date('FechaDeCompra')->nullable();
            $table->date('FechaMantenimiento');
            $table->date('FechaReprogramada')->nullable();
            $table->text('Comentario')->nullable();
            $table->string('Estatus', 20)->default('Pendiente');
            $table->unsignedBigInteger('RealizadoPor')->nullable();
            $table->dateTime('FechaRealizado')->nullable();
            $table->timestamps();

            $table->unique(['EmpleadoID', 'InventarioID', 'FechaMantenimiento'], 'uniq_mantenimiento_programado');
            $table->index(['FechaMantenimiento', 'Estatus']);
            $table->index(['AnioProgramacion', 'Estatus']);
        });

        Schema::create('mantenimiento_chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('mantenimiento_id');
            $table->text('mensaje');
            $table->string('remitente');
            $table->string('correo_remitente')->nullable();
            $table->string('nombre_remitente')->nullable();
            $table->text('contenido_correo')->nullable();
            $table->string('message_id')->nullable()->index();
            $table->string('thread_id')->nullable()->index();
            $table->json('adjuntos')->nullable();
            $table->boolean('es_correo')->default(false);
            $table->boolean('leido')->default(false);
            $table->unsignedInteger('notificaciones_pendientes')->default(0);
            $table->timestamps();

            $table->index(['mantenimiento_id', 'created_at']);
        });

        Schema::create('tickets_mantenimiento', function (Blueprint $table) {
            $table->bigIncrements('MantenimientoID');
            $table->unsignedBigInteger('EmpleadoID')->nullable();
            $table->text('Descripcion');
            $table->string('Categoria')->nullable();
            $table->string('Prioridad')->nullable();
            $table->string('Estatus')->default('Pendiente');
            $table->unsignedBigInteger('ResponsableID')->nullable();
            $table->json('imagen')->nullable();
            $table->timestamp('FechaInicioProgreso')->nullable();
            $table->timestamp('FechaFinProgreso')->nullable();
            $table->timestamp('FechaInicioPausa')->nullable();
            $table->decimal('HorasPausadas')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tickets_mantenimiento');
        Schema::dropIfExists('mantenimiento_chats');
        Schema::dropIfExists('mantenimientos');
        Schema::enableForeignKeyConstraints();
    }
};
