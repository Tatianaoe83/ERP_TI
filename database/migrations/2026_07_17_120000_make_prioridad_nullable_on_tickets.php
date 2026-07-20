<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY Prioridad ENUM('Baja','Media','Alta') NULL DEFAULT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY Prioridad ENUM('Baja','Media','Alta') NOT NULL DEFAULT 'Baja'");
    }
};
