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

            $table->string('NombreLicencia', 255)->nullable();

            // Los dos únicos campos capturables sobre la corrida ya generada.
            $table->boolean('tiene_licencia')->default(false);

            // Tri-estado a propósito: null = todavía no se revisó si es original.
            $table->boolean('original')->nullable()->default(null);

            $table->timestamps();

            // Una licencia por corrida: el equipo ya no forma parte de la llave.
            $table->unique(['auditoria_id', 'NombreLicencia'], 'auditoria_licencia_unico');
            $table->index('original');
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
