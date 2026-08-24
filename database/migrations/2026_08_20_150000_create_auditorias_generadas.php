<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('auditorias_equipos');

        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->string('Folio', 30)->unique();
            $table->unsignedBigInteger('id_empleado')->nullable();
            $table->string('generada_por_nombre', 150)->nullable();

            // Alcance de la corrida: un empleado auditado y una modalidad de equipo.
            // El tipo de persona y la gerencia NO se guardan: salen del join con
            // empleados, así que duplicarlos sólo abre la puerta a que se desfasen.
            $table->integer('EmpleadoID')->nullable()->index();
            $table->unsignedTinyInteger('tipoEquipo')->nullable();

            $table->timestamps();
            // El momento de la corrida es created_at; no hay una segunda fecha.
            $table->index('created_at');
        });

        Schema::create('auditorias_equipos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('auditoria_id');
            $table->unsignedInteger('InventarioID');

            $table->string('CategoriaEquipo', 150)->nullable();
            $table->string('Marca', 150)->nullable();
            $table->string('Modelo', 150)->nullable();
            $table->string('NumSerie', 100)->nullable();
            $table->string('Folio', 50)->nullable();
            $table->string('GerenciaEquipo', 150)->nullable();
            $table->string('NombreEmpleado', 200)->nullable();
            $table->unsignedTinyInteger('tipoEquipo')->default(0);
            $table->unsignedTinyInteger('grupo')->default(2);
            $table->text('licencias')->nullable();
            $table->unsignedSmallInteger('licencias_piratas')->default(0);

            $table->timestamps();

            $table->unique(['auditoria_id', 'InventarioID'], 'auditoria_equipo_unico');
            $table->index('grupo');
            $table->index('tipoEquipo');
            $table->foreign('auditoria_id')->references('id')->on('auditorias')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias_equipos');
        Schema::dropIfExists('auditorias');
    }
};
