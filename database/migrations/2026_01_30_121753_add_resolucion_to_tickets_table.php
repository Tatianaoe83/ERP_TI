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
        Schema::table('tickets', function (Blueprint $table) {
            // Agregamos la columna 'Resolucion' de tipo texto, nullable
            // y la posicionamos después de 'Estatus' para mantener orden
            $table->text('Resolucion')->nullable()->after('Estatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Eliminar la columna si hacemos rollback
            $table->dropColumn('Resolucion');
        });
    }
};