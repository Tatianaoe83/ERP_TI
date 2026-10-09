<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeFrecuenciaDePagoNullableOnInsumos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE insumos MODIFY FrecuenciaDePago VARCHAR(50) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE insumos MODIFY FrecuenciaDePago VARCHAR(50) NOT NULL');
    }
}
