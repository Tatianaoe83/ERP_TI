<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets_mantenimiento', function (Blueprint $table) {
            $table->id('MantenimientoID');
            $table->unsignedBigInteger('EmpleadoID')->nullable();
            $table->string('NombreSolicitante');
            $table->string('Correo');
            $table->string('AreaDepartamento')->nullable();
            $table->string('Asunto');
            $table->text('Descripcion');
            $table->string('Categoria')->nullable();
            $table->string('Prioridad')->default('Baja');
            $table->string('Estatus')->default('Pendiente');
            $table->string('Responsable')->nullable();
            $table->json('imagen')->nullable();
            $table->timestamp('FechaInicioProgreso')->nullable();
            $table->timestamp('FechaFinProgreso')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets_mantenimiento');
    }
};
