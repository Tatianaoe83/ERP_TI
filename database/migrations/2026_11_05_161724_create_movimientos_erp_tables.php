<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cargos_movimiento', function (Blueprint $table) {
            $table->increments('CargoID');
            $table->string('Clave', 40);
            $table->text('Descripcion');
            $table->string('UnidadNegocio', 80);
            $table->string('Autoriza', 40);
            $table->timestamps();
        });

        Schema::create('solicitudes_movimiento', function (Blueprint $table) {
            $table->increments('SolicitudMovimientoID');
            $table->unsignedInteger('EmpleadoID');
            $table->unsignedInteger('ObraID');
            $table->unsignedInteger('CargoID');
            $table->unsignedInteger('ResidenteControlID');
            $table->unsignedInteger('AutorizadorID')->nullable();
            $table->string('AutorizadorRol', 80);
            $table->text('Justificacion');
            $table->text('Respuesta')->nullable();
            $table->string('Estatus', 30)->default('Por autorizar')->index();
            $table->string('Folio', 40)->nullable()->unique();
            $table->timestamp('autorizado_en')->nullable();
            $table->timestamp('aplicado_en')->nullable();
            $table->timestamps();
        });

        Schema::create('solicitud_movimiento_lineas', function (Blueprint $table) {
            $table->increments('LineaID');
            $table->unsignedInteger('SolicitudMovimientoID')->index();
            $table->unsignedSmallInteger('Orden');
            $table->unsignedInteger('CargoID');
            $table->string('CodigoInsumo', 40);
            $table->string('Descripcion', 180);
            $table->string('Partida', 120);
            $table->decimal('Cantidad', 14);
            $table->decimal('Precio', 14);
            $table->decimal('Importe', 14);
            $table->string('Justificacion', 500);
            $table->timestamps();
        });

        Schema::create('solicitud_movimiento_archivos', function (Blueprint $table) {
            $table->increments('ArchivoID');
            $table->unsignedInteger('SolicitudMovimientoID');
            $table->string('Ruta', 255);
            $table->string('Nombre', 180);
            $table->string('Tipo', 20)->default('soporte');
            $table->timestamps();
        });

        Schema::create('solicitud_movimiento_comentarios', function (Blueprint $table) {
            $table->increments('ComentarioID');
            $table->unsignedInteger('SolicitudMovimientoID');
            $table->unsignedInteger('UserID')->nullable();
            $table->string('Autor', 150);
            $table->text('Texto');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('solicitud_movimiento_comentarios');
        Schema::dropIfExists('solicitud_movimiento_archivos');
        Schema::dropIfExists('solicitud_movimiento_lineas');
        Schema::dropIfExists('solicitudes_movimiento');
        Schema::dropIfExists('cargos_movimiento');
        Schema::enableForeignKeyConstraints();
    }
};
