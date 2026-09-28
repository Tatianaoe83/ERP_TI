<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ticket_tareas') || Schema::hasColumn('ticket_tareas', 'asignados_ids')) {
            return;
        }

        Schema::table('ticket_tareas', function (Blueprint $table) {
            $table->string('asignados_ids', 255)->nullable()->after('asignado_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('ticket_tareas') && Schema::hasColumn('ticket_tareas', 'asignados_ids')) {
            Schema::table('ticket_tareas', function (Blueprint $table) {
                $table->dropColumn('asignados_ids');
            });
        }
    }
};
