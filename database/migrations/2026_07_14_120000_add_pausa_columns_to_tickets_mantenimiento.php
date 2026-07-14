<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El SLA no debe correr mientras la solicitud está Pausada. Para poder descontar ese tiempo
     * hay que registrarlo: FechaInicioPausa marca la pausa en curso y HorasPausadas acumula las
     * horas laborales de las pausas ya cerradas.
     */
    public function up(): void
    {
        Schema::table('tickets_mantenimiento', function (Blueprint $table) {
            $table->timestamp('FechaInicioPausa')->nullable()->after('FechaFinProgreso');
            $table->decimal('HorasPausadas', 8, 2)->default(0)->after('FechaInicioPausa');
        });
    }

    public function down(): void
    {
        Schema::table('tickets_mantenimiento', function (Blueprint $table) {
            $table->dropColumn(['FechaInicioPausa', 'HorasPausadas']);
        });
    }
};
