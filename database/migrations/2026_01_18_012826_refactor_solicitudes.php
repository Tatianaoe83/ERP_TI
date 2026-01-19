<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropColumn([
                'SupervisorID',

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
                'ComentarioAdministracion',

                'Tipo'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->unsignedBigInteger('SupervisorID')->nullable();

            $table->string('AprobacionSupervisor')->nullable();
            $table->dateTime('FechaAprobacionSupervisor')->nullable();
            $table->unsignedBigInteger('SupervisorAprobadorID')->nullable();
            $table->text('ComentarioSupervisor')->nullable();

            $table->string('AprobacionGerencia')->nullable();
            $table->dateTime('FechaAprobacionGerencia')->nullable();
            $table->unsignedBigInteger('GerenteAprobadorID')->nullable();
            $table->text('ComentarioGerencia')->nullable();

            $table->string('AprobacionAdministracion')->nullable();
            $table->dateTime('FechaAprobacionAdministracion')->nullable();
            $table->unsignedBigInteger('AdministradorAprobadorID')->nullable();
            $table->text('ComentarioAdministracion')->nullable();
        });
    }
};
