<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ticket_tareas') || ! Schema::hasColumn('ticket_tareas', 'fecha_compromiso')) {
            return;
        }

        Schema::table('ticket_tareas', function (Blueprint $table) {
            $table->date('fecha_compromiso')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ticket_tareas') || ! Schema::hasColumn('ticket_tareas', 'fecha_compromiso')) {
            return;
        }

        Schema::table('ticket_tareas', function (Blueprint $table) {
            $table->date('fecha_compromiso')->nullable(false)->change();
        });
    }
};
