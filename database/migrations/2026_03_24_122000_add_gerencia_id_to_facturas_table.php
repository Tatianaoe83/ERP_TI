<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGerenciaIdToFacturasTable extends Migration
{
    public function up()
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->unsignedBigInteger('GerenciaID')->nullable()->after('SolicitudID');
        });
    }

    public function down()
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn('GerenciaID');
        });
    }
}