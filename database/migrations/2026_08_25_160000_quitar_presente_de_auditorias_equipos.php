<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Del equipo no se verifica presencia.
 *
 * La auditoría es de licencias; los equipos entran para dejar constancia de qué
 * resguardaba el empleado ese día y para poder anotar hallazgos. Pedir un "¿está?"
 * por máquina agregaba captura que nadie usa. Queda sólo la observación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auditorias_equipos', function (Blueprint $table) {
            $table->dropIndex(['presente']);
            $table->dropColumn('presente');
        });
    }

    public function down(): void
    {
        Schema::table('auditorias_equipos', function (Blueprint $table) {
            $table->boolean('presente')->nullable()->default(null)->after('InventarioID');
            $table->index('presente');
        });
    }
};
