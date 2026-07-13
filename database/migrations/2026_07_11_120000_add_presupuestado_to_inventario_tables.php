<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tablas = ['inventarioinsumo', 'inventarioequipo', 'inventariolineas'];

    /**
     * Marca si la asignación entra al presupuesto. Sólo se activa para empleados
     * FISICA y EXTRAORDINARIO, que son los que alimentan los reportes de presupuesto.
     */
    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            if (Schema::hasColumn($tabla, 'Presupuestado')) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->boolean('Presupuestado')->default(false)->index();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            if (! Schema::hasColumn($tabla, 'Presupuestado')) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn('Presupuestado');
            });
        }
    }
};
