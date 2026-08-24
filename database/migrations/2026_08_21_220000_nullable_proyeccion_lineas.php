<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventariolineas')) {
            return;
        }

        Schema::table('inventariolineas', function (Blueprint $table) {
            $table->string('NumTelefonico', 50)->nullable()->change();
            $table->string('CuentaPadre', 100)->nullable()->change();
            $table->string('CuentaHija', 100)->nullable()->change();
            $table->date('FechaAsignacion')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventariolineas')) {
            return;
        }

        Schema::table('inventariolineas', function (Blueprint $table) {
            $table->string('NumTelefonico', 50)->nullable(false)->change();
            $table->string('CuentaPadre', 100)->nullable(false)->change();
            $table->string('CuentaHija', 100)->nullable(false)->change();
            $table->date('FechaAsignacion')->nullable(false)->change();
        });
    }
};
