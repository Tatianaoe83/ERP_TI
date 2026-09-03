<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ticket_tareas')) {
            return;
        }

        Schema::table('ticket_tareas', function (Blueprint $table) {
            $table->dropForeign(['asignado_id']);
        });

        Schema::table('ticket_tareas', function (Blueprint $table) {
            $table->integer('asignado_id')->nullable()->change();
            $table->foreign('asignado_id')->references('EmpleadoID')->on('empleados')->nullOnDelete();
        });

        if (Schema::hasTable('ticket_tarea_metricas')) {
            Schema::table('ticket_tarea_metricas', function (Blueprint $table) {
                if (Schema::hasColumn('ticket_tarea_metricas', 'asignado_id')) {
                    $table->dropForeign(['asignado_id']);
                    $table->dropColumn('asignado_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ticket_tareas')) {
            return;
        }

        Schema::table('ticket_tareas', function (Blueprint $table) {
            $table->dropForeign(['asignado_id']);
        });

        Schema::table('ticket_tareas', function (Blueprint $table) {
            $table->integer('asignado_id')->nullable(false)->change();
            $table->foreign('asignado_id')->references('EmpleadoID')->on('empleados');
        });

        if (Schema::hasTable('ticket_tarea_metricas') && ! Schema::hasColumn('ticket_tarea_metricas', 'asignado_id')) {
            Schema::table('ticket_tarea_metricas', function (Blueprint $table) {
                $table->integer('asignado_id')->nullable()->after('descripcion');
                $table->foreign('asignado_id')->references('EmpleadoID')->on('empleados')->nullOnDelete();
            });
        }
    }
};
