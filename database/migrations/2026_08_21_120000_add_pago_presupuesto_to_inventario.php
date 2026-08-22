<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('inventarioinsumo', 'MesDePago')) {
            Schema::table('inventarioinsumo', function (Blueprint $table) {
                $table->string('MesDePago', 191)->nullable()->change();
            });
        }

        Schema::table('inventariolineas', function (Blueprint $table) {
            if (! Schema::hasColumn('inventariolineas', 'FrecuenciaDePago')) {
                $table->string('FrecuenciaDePago', 40)->nullable()->after('Presupuestado');
            }
            if (! Schema::hasColumn('inventariolineas', 'MesDePago')) {
                $table->string('MesDePago', 191)->nullable()->after('FrecuenciaDePago');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventariolineas', function (Blueprint $table) {
            if (Schema::hasColumn('inventariolineas', 'MesDePago')) {
                $table->dropColumn('MesDePago');
            }
            if (Schema::hasColumn('inventariolineas', 'FrecuenciaDePago')) {
                $table->dropColumn('FrecuenciaDePago');
            }
        });
    }
};
