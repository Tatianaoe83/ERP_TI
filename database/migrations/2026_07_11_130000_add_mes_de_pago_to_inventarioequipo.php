<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mes en que se planea pagar el equipo. Hasta ahora el calendario de pagos colgaba el
     * gasto del mes de FechaDeCompra (la fecha en que se adquirió), que no sirve para
     * presupuestar una compra futura. Con esta columna el mes se elige a mano, igual que
     * en inventarioinsumo.
     */
    public function up(): void
    {
        if (Schema::hasColumn('inventarioequipo', 'MesDePago')) {
            return;
        }

        Schema::table('inventarioequipo', function (Blueprint $table) {
            $table->string('MesDePago', 20)->nullable()->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('inventarioequipo', 'MesDePago')) {
            return;
        }

        Schema::table('inventarioequipo', function (Blueprint $table) {
            $table->dropColumn('MesDePago');
        });
    }
};
