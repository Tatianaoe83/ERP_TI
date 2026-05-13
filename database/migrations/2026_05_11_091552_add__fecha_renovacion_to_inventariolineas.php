<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFechaRenovacionToInventariolineas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventariolineas', function (Blueprint $table) {
        $table->date('FechaRenovacion')->nullable()->after('MontoRenovacionFianza');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    
}
