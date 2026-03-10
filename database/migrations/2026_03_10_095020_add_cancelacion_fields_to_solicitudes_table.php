<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancelacionFieldsToSolicitudesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            // Asumiendo que tu tabla de usuarios/empleados usa id o EmpleadoID
            $table->unsignedBigInteger('cancelado_por')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamp('fecha_cancelacion')->nullable();
            $table->foreign('cancelado_por')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropColumn(['cancelado_por', 'motivo_cancelacion', 'fecha_cancelacion']);
        });
    }
}
