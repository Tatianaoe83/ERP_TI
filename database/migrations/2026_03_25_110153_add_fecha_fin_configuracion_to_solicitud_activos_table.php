<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFechaFinConfiguracionToSolicitudActivosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up()
{
    Schema::table('solicitud_activos', function (Blueprint $table) {
    $table->timestamp('fecha_fin_configuracion')->nullable();});
}

public function down()
{
    Schema::table('solicitud_activos', function (Blueprint $table) {
        $table->dropColumn('fecha_fin_configuracion');
    });
}
}
