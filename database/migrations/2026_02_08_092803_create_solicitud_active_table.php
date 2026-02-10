<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('solicitud_activos', function (Blueprint $table) {
            $table->bigIncrements('SolicitudActivoID');
            $table->integer('SolicitudID');
            $table->unsignedBigInteger('CotizacionID');
            $table->integer('NumeroPropuesta')->default(0);
            $table->unsignedInteger('UnidadIndex')->default(1);

            $table->string('FacturaPath', 255)->nullable();
            $table->date('FechaEntrega')->nullable();

            $table->integer('EmpleadoID')->nullable();
            $table->integer('DepartamentoID')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();

            $table->unique(['SolicitudID', 'CotizacionID', 'UnidadIndex'], 'uq_sol_activo_unit');

            $table->foreign('SolicitudID')->references('SolicitudID')->on('solicitudes');
            $table->foreign('CotizacionID')->references('CotizacionID')->on('cotizaciones');
            $table->foreign('EmpleadoID')->references('EmpleadoID')->on('empleados');
            $table->foreign('DepartamentoID')->references('DepartamentoID')->on('departamentos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_activos');
    }
};
