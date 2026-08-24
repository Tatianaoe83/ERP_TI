<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (! Schema::hasColumn('inventarioequipo', 'tipoEquipo')) {
            return;
        }

        DB::statement("ALTER TABLE inventarioequipo MODIFY tipoEquipo VARCHAR(1) NOT NULL DEFAULT '0'");
        DB::statement("UPDATE inventarioequipo SET tipoEquipo = '3' WHERE tipoEquipo = '2'");
        DB::statement("ALTER TABLE inventarioequipo MODIFY tipoEquipo ENUM('0', '1', '2', '3') NOT NULL DEFAULT '0'");

        if (Schema::hasTable('auditorias_equipos')) {
            DB::statement("UPDATE auditorias_equipos SET tipoEquipo = 3 WHERE tipoEquipo = 2");
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('inventarioequipo', 'tipoEquipo')) {
            return;
        }

        DB::statement("ALTER TABLE inventarioequipo MODIFY tipoEquipo VARCHAR(1) NOT NULL DEFAULT '0'");
        DB::statement("UPDATE inventarioequipo SET tipoEquipo = '0' WHERE tipoEquipo = '2'");
        DB::statement("UPDATE inventarioequipo SET tipoEquipo = '2' WHERE tipoEquipo = '3'");
        DB::statement("ALTER TABLE inventarioequipo MODIFY tipoEquipo ENUM('0', '1', '2') NOT NULL DEFAULT '0'");

        if (Schema::hasTable('auditorias_equipos')) {
            DB::statement("UPDATE auditorias_equipos SET tipoEquipo = 0 WHERE tipoEquipo = 2");
            DB::statement("UPDATE auditorias_equipos SET tipoEquipo = 2 WHERE tipoEquipo = 3");
        }
    }
};
