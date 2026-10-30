<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLineasSolicitudMovimiento extends Migration
{
    public function up()
    {
        Schema::table('solicitudes_movimiento', function (Blueprint $table) {
            $table->text('Respuesta')->nullable()->after('Justificacion');
        });

        Schema::create('solicitud_movimiento_lineas', function (Blueprint $table) {
            $table->increments('LineaID');
            $table->unsignedInteger('SolicitudMovimientoID');
            $table->unsignedSmallInteger('Orden');
            $table->unsignedInteger('CargoID');
            $table->string('CodigoInsumo', 40);
            $table->string('Descripcion', 180);
            $table->string('Partida', 120);
            $table->decimal('Cantidad', 14, 2);
            $table->decimal('Precio', 14, 2);
            $table->decimal('Importe', 14, 2);
            $table->string('Justificacion', 500);
            $table->timestamps();

            $table->index('SolicitudMovimientoID');
        });
    }

    public function down()
    {
        Schema::dropIfExists('solicitud_movimiento_lineas');
        Schema::table('solicitudes_movimiento', function (Blueprint $table) {
            $table->dropColumn('Respuesta');
        });
    }
}
