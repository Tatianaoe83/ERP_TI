<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificacionesPendientesAndFechaUltimaRespuestaToTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('ticket_chats', function (Blueprint $table) {
        $table->integer('notificaciones_pendientes')->default(0)->after('leido');
        $table->timestamp('fecha_ultima_respuesta')->nullable()->after('notificaciones_pendientes');
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_chats', function (Blueprint $table) {
        $table->dropColumn(['notificaciones_pendientes', 'fecha_ultima_respuesta']);
    });
    }
}
