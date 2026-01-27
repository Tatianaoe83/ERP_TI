<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNumeroParteToCotizacionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->string('NumeroParte', 255)->nullable()->after('NumeroPropuesta');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn('NumeroParte');
        });
    }
}
