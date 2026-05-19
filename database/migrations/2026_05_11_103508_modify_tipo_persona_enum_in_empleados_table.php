<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyTipoPersonaEnumInEmpleadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
    {
        DB::statement("
            ALTER TABLE empleados 
            CHANGE tipo_persona tipo_persona 
            ENUM('FISICA','REFERENCIADO','EXTRAORDINARIO')
            CHARACTER SET utf8mb4 
            COLLATE utf8mb4_general_ci 
            NULL DEFAULT 'FISICA'
        ");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */

}
