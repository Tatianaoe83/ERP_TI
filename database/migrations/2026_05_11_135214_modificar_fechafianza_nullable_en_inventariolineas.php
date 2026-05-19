<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventariolineas', function (Blueprint $table) {
            $table->date('FechaFianza')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventariolineas', function (Blueprint $table) {
            $table->date('FechaFianza')->nullable(false)->change();
        });
    }
};