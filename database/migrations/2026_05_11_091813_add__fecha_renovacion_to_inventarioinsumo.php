<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFechaRenovacionToInventarioinsumo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventarioinsumo', function (Blueprint $table) {
            $table->date('FechaRenovacion')->nullable()->after('FrecuenciaDePago');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventarioinsumo', function (Blueprint $table) {
            //
        });
    }
}
