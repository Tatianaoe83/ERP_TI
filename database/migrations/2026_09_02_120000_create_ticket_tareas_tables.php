<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_tarea_metricas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->integer('asignado_id')->nullable();
            $table->unsignedTinyInteger('dia_compromiso')->default(5);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('asignado_id')->references('EmpleadoID')->on('empleados')->nullOnDelete();
        });

        Schema::create('ticket_tareas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->text('razon')->nullable();
            $table->integer('asignado_id');
            $table->bigInteger('creado_por_user_id')->nullable();
            $table->date('fecha_compromiso');
            $table->string('estatus', 30)->default('pendiente');
            $table->string('tipo', 20)->default('evento');
            $table->unsignedBigInteger('metrica_id')->nullable();
            $table->unsignedTinyInteger('periodo_mes')->nullable();
            $table->unsignedSmallInteger('periodo_anio')->nullable();
            $table->string('prioridad', 20)->default('normal');
            $table->timestamp('completada_at')->nullable();
            $table->timestamps();

            $table->foreign('asignado_id')->references('EmpleadoID')->on('empleados');
            $table->foreign('metrica_id')->references('id')->on('ticket_tarea_metricas')->nullOnDelete();
            $table->unique(['metrica_id', 'periodo_mes', 'periodo_anio'], 'ticket_tareas_metrica_periodo_unique');
            $table->index(['estatus', 'fecha_compromiso']);
            $table->index(['asignado_id', 'estatus']);
        });

        Schema::create('ticket_tarea_historial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tarea_id');
            $table->bigInteger('user_id')->nullable();
            $table->string('accion', 40);
            $table->text('motivo')->nullable();
            $table->date('fecha_compromiso_anterior')->nullable();
            $table->date('fecha_compromiso_nueva')->nullable();
            $table->integer('asignado_anterior_id')->nullable();
            $table->integer('asignado_nuevo_id')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->foreign('tarea_id')->references('id')->on('ticket_tareas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_tarea_historial');
        Schema::dropIfExists('ticket_tareas');
        Schema::dropIfExists('ticket_tarea_metricas');
    }
};
