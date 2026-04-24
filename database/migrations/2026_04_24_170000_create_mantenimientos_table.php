<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('EmpleadoID');
            $table->unsignedInteger('InventarioID');
            $table->string('NombreEmpleado', 150);
            $table->string('NombreGerencia', 150)->nullable();
            $table->string('TipoMantenimiento', 20);
            $table->date('FechaMantenimiento');
            $table->string('Estatus', 20)->default('Pendiente');
            $table->unsignedBigInteger('RealizadoPor')->nullable();
            $table->dateTime('FechaRealizado')->nullable();
            $table->timestamps();

            $table->index(['FechaMantenimiento', 'Estatus']);
            $table->unique(['EmpleadoID', 'InventarioID', 'FechaMantenimiento'], 'uniq_mantenimiento_programado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
