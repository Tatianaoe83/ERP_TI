<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFechaRenovacionToInsumos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insumos', function (Blueprint $table) {
            $table->date('FechaRenovacion')->nullable()->after('Observaciones');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

}
