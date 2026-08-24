<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('auditorias_equipos', 'observaciones')) {
            return;
        }

        Schema::table('auditorias_equipos', function (Blueprint $table) {
            $table->text('observaciones')->nullable()->after('original');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('auditorias_equipos', 'observaciones')) {
            return;
        }

        Schema::table('auditorias_equipos', function (Blueprint $table) {
            $table->dropColumn('observaciones');
        });
    }
};
