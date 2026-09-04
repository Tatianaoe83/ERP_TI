<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_tareas', function (Blueprint $table) {
            $table->timestamp('notificado_creacion_at')->nullable()->after('completada_at');
            $table->timestamp('notificado_critica_at')->nullable()->after('notificado_creacion_at');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tareas', function (Blueprint $table) {
            $table->dropColumn(['notificado_creacion_at', 'notificado_critica_at']);
        });
    }
};
