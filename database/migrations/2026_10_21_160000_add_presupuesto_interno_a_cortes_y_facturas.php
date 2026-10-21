<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cortes', function (Blueprint $table) {
            $table->unsignedInteger('PresupuestoInternoID')->nullable()->after('GerenciaID');
            $table->foreign('PresupuestoInternoID')
                ->references('PresupuestoInternoID')
                ->on('presupuestos_internos')
                ->nullOnDelete();
            $table->index(['PresupuestoInternoID', 'Anio']);
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->unsignedInteger('PresupuestoInternoID')->nullable()->after('GerenciaID');
            $table->foreign('PresupuestoInternoID')
                ->references('PresupuestoInternoID')
                ->on('presupuestos_internos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropForeign(['PresupuestoInternoID']);
            $table->dropColumn('PresupuestoInternoID');
        });

        Schema::table('cortes', function (Blueprint $table) {
            $table->dropForeign(['PresupuestoInternoID']);
            $table->dropIndex(['PresupuestoInternoID', 'Anio']);
            $table->dropColumn('PresupuestoInternoID');
        });
    }
};
