<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnidadToCotizacionesTable extends Migration
{
    /**
     * Run the migrations.
     * Unidad por equipo (PIEZA, Caja, etc.); mismo valor para todas las filas del mismo NumeroPropuesta.
     */
    public function up()
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->string('Unidad', 100)->nullable()->after('Cantidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn('Unidad');
        });
    }
}
