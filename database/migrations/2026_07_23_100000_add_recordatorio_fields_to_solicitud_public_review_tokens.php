<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('solicitud_public_review_tokens', function (Blueprint $table) {
            // Momento en que realmente se envió el correo con este enlace.
            // Los tokens de gerencia y administración se crean por adelantado,
            // así que sin esta marca no se puede saber si el aprobador ya fue notificado.
            $table->dateTime('notified_at')->nullable()->after('used_at');
            $table->dateTime('last_reminder_at')->nullable()->after('notified_at');
            $table->unsignedSmallInteger('reminders_sent')->default(0)->after('last_reminder_at');

            $table->index(['notified_at', 'last_reminder_at'], 'idx_tokens_recordatorios');
        });

        // Los tokens que ya existen y siguen activos se consideran notificados
        // al momento de su creación, para que entren al ciclo de recordatorios.
        DB::table('solicitud_public_review_tokens')
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->whereNull('notified_at')
            ->update(['notified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('solicitud_public_review_tokens', function (Blueprint $table) {
            $table->dropIndex('idx_tokens_recordatorios');
            $table->dropColumn(['notified_at', 'last_reminder_at', 'reminders_sent']);
        });
    }
};
