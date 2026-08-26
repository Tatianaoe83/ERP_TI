<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `auditorias_equipos` sólo guardaba el InventarioID: la ficha (marca, modelo,
 * serie, folio, categoría) se leía siempre en vivo desde `inventarioequipo`. Si
 * ese registro se borra alguna vez del inventario, la corrida vieja se queda sin
 * forma de decir qué equipo era.
 *
 * Se congela una copia de la ficha al generar la corrida: mientras el equipo siga
 * en el inventario se sigue leyendo en vivo (por si se corrigió un dato), pero si
 * ya no existe, esta copia es lo único que queda para identificarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auditorias_equipos', function (Blueprint $table) {
            $table->string('CategoriaEquipo', 100)->nullable()->after('InventarioID');
            $table->string('Marca', 150)->nullable()->after('CategoriaEquipo');
            $table->string('Modelo', 100)->nullable()->after('Marca');
            $table->string('NumSerie', 100)->nullable()->after('Modelo');
            $table->string('Folio', 50)->nullable()->after('NumSerie');
        });
    }

    public function down(): void
    {
        Schema::table('auditorias_equipos', function (Blueprint $table) {
            $table->dropColumn(['CategoriaEquipo', 'Marca', 'Modelo', 'NumSerie', 'Folio']);
        });
    }
};
