<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCanceladoPorEmpleadoIdToSolicitudesTable extends Migration
{
    /**
     * El gerente cancela desde su enlace sin iniciar sesión: no hay users.id que guardar
     * en cancelado_por, así que se registra su EmpleadoID aparte.
     */
    public function up()
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->unsignedBigInteger('cancelado_por_empleado_id')->nullable()->after('cancelado_por');
        });
    }

    public function down()
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropColumn('cancelado_por_empleado_id');
        });
    }
}
