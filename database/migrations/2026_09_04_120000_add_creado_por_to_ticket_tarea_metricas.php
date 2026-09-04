<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_tarea_metricas', function (Blueprint $table) {
            $table->bigInteger('creado_por_user_id')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tarea_metricas', function (Blueprint $table) {
            $table->dropColumn('creado_por_user_id');
        });
    }
};
