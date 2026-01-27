<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyEstatusColumnSizeInSolicitudesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Cambiar el tamaño de la columna Estatus para permitir valores más largos
        // Los valores más largos son: "Pendiente Aprobación Administración" (39 caracteres)
        // Usamos 100 caracteres para tener margen
        DB::statement('ALTER TABLE solicitudes MODIFY COLUMN Estatus VARCHAR(100) NOT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revertir al tamaño original (255 es el default de Laravel para string)
        DB::statement('ALTER TABLE solicitudes MODIFY COLUMN Estatus VARCHAR(255) NOT NULL');
    }
}
