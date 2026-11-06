<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPausaToTickets extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE tickets MODIFY Estatus ENUM('Pendiente','En progreso','Pausado','Cerrado') NULL DEFAULT 'Pendiente'");

        Schema::table('tickets', function (Blueprint $table) {
            $table->dateTime('FechaInicioPausa')->nullable()->after('FechaFinProgreso');
            $table->decimal('HorasPausadas', 8, 2)->default(0)->after('FechaInicioPausa');
        });
    }

    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['FechaInicioPausa', 'HorasPausadas']);
        });

        DB::statement("ALTER TABLE tickets MODIFY Estatus ENUM('Pendiente','En progreso','Cerrado') NULL DEFAULT 'Pendiente'");
    }
}
