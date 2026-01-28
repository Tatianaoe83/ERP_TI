<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddEquipoNuevoToMotivosInSolicitudes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        
        // Si usas MySQL/MariaDB:
        DB::statement("ALTER TABLE solicitudes MODIFY COLUMN Motivo ENUM('Nuevo Ingreso', 'Reemplazo por fallo o descompostura', 'Renovación', 'Equipo Nuevo') NULL");
        

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // DB::statement("ALTER TABLE solicitudes MODIFY COLUMN Motivo ENUM('Nuevo Ingreso', 'Reemplazo por fallo o descompostura', 'Renovación') NULL");
    }
}