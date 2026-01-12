<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAprobacionFieldsToSolicitudesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            // Campos de aprobación
            $table->enum('AprobacionSupervisor', ['Pendiente', 'Aprobado', 'Rechazado'])->default('Pendiente')->after('Estatus');
            $table->timestamp('FechaAprobacionSupervisor')->nullable()->after('AprobacionSupervisor');
            $table->integer('SupervisorAprobadorID')->nullable()->after('FechaAprobacionSupervisor');
            $table->text('ComentarioSupervisor')->nullable()->after('SupervisorAprobadorID');
            
            $table->enum('AprobacionGerencia', ['Pendiente', 'Aprobado', 'Rechazado'])->default('Pendiente')->after('ComentarioSupervisor');
            $table->timestamp('FechaAprobacionGerencia')->nullable()->after('AprobacionGerencia');
            $table->integer('GerenteAprobadorID')->nullable()->after('FechaAprobacionGerencia');
            $table->text('ComentarioGerencia')->nullable()->after('GerenteAprobadorID');
            
            $table->enum('AprobacionAdministracion', ['Pendiente', 'Aprobado', 'Rechazado'])->default('Pendiente')->after('ComentarioGerencia');
            $table->timestamp('FechaAprobacionAdministracion')->nullable()->after('AprobacionAdministracion');
            $table->integer('AdministradorAprobadorID')->nullable()->after('FechaAprobacionAdministracion');
            $table->text('ComentarioAdministracion')->nullable()->after('AdministradorAprobadorID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropColumn([
                'AprobacionSupervisor',
                'FechaAprobacionSupervisor',
                'SupervisorAprobadorID',
                'ComentarioSupervisor',
                'AprobacionGerencia',
                'FechaAprobacionGerencia',
                'GerenteAprobadorID',
                'ComentarioGerencia',
                'AprobacionAdministracion',
                'FechaAprobacionAdministracion',
                'AdministradorAprobadorID',
                'ComentarioAdministracion'
            ]);
        });
    }
}
