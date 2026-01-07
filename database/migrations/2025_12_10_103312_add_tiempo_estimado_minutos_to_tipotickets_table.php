<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTiempoEstimadoMinutosToTipoticketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tipotickets', function (Blueprint $table) {
            $table->integer('TiempoEstimadoMinutos')->nullable()->after('NombreTipo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tipotickets', function (Blueprint $table) {
            $table->dropColumn('TiempoEstimadoMinutos');
        });
    }
}
