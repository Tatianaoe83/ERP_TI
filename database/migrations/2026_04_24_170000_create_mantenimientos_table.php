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
            $table->unsignedSmallInteger('AnioProgramacion');
            $table->unsignedInteger('EmpleadoID');
            $table->unsignedInteger('InventarioID');
            $table->string('TipoMantenimiento', 20);
            $table->string('Folio', 100)->nullable();
            $table->date('FechaDeCompra')->nullable();
            $table->date('FechaMantenimiento');
            $table->date('FechaReprogramada')->nullable();
            $table->text('Comentario')->nullable();
            $table->string('Estatus', 20)->default('Pendiente');
            $table->unsignedBigInteger('RealizadoPor')->nullable();
            $table->dateTime('FechaRealizado')->nullable();
            $table->timestamps();

            $table->index(['AnioProgramacion', 'Estatus']);
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
