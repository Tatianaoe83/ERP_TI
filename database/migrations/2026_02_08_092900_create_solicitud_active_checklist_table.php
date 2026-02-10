<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('solicitud_activo_checklists', function (Blueprint $table) {
            $table->bigIncrements('SolicitudActivoChecklistID');
            $table->unsignedBigInteger('SolicitudActivoID');
            $table->unsignedBigInteger('DepartamentoRequerimientoID');

            $table->boolean('completado')->default(false);
            $table->string('responsable', 80)->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();

            $table->unique(['SolicitudActivoID', 'DepartamentoRequerimientoID'], 'uq_activo_req');

            $table->foreign('SolicitudActivoID')->references('SolicitudActivoID')->on('solicitud_activos')->onDelete('cascade');
            $table->foreign('DepartamentoRequerimientoID')->references('id')->on('departamento_requerimientos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_activo_checklists');
    }
};
