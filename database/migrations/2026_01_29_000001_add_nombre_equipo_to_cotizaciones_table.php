<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNombreEquipoToCotizacionesTable extends Migration
{
    /**
     * Run the migrations.
     * Nombre del equipo por cotización (mismo valor para todas las filas del mismo NumeroPropuesta).
     */
    public function up()
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->string('NombreEquipo', 255)->nullable()->after('NumeroPropuesta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn('NombreEquipo');
        });
    }
}
