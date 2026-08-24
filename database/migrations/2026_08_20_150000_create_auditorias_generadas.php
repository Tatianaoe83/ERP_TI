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

            $table->integer('EmpleadoID')->nullable()->index();
            $table->unsignedTinyInteger('tipoEquipo')->nullable();

            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('auditorias_equipos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('auditoria_id');
            $table->unsignedInteger('InventarioID');

            $table->string('NombreLicencia', 255)->nullable();
            $table->boolean('tiene_licencia')->default(false);
            $table->boolean('pirata')->default(false);
            $table->boolean('en_dominio')->default(false);

            $table->timestamps();

            // La unicidad ya es por licencia: el mismo equipo se repite una vez por cada una.
            $table->unique(['auditoria_id', 'InventarioID', 'NombreLicencia'], 'auditoria_equipo_licencia_unico');
            $table->index('grupo');
            $table->index('tipoEquipo');
            $table->index('pirata');
            $table->index('en_dominio');
            $table->index('tiene_licencia');
            $table->foreign('auditoria_id')->references('id')->on('auditorias')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias_equipos');
        Schema::dropIfExists('auditorias');
    }
};
