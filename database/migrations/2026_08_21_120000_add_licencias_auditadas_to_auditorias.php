<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->json('licencias_auditadas')->nullable()->after('tipoEquipo');
            $table->unsignedSmallInteger('total_licencias_auditadas')->default(0)->after('licencias_auditadas');
        });
    }

    public function down(): void
    {
        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropColumn(['licencias_auditadas', 'total_licencias_auditadas']);
        });
    }
};
