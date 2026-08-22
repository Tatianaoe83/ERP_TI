<?php

use App\Helpers\PagoMeses;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('inventarioequipo', 'MesDePago')) {
            Schema::table('inventarioequipo', function (Blueprint $table) {
                $table->string('MesDePago', 191)->nullable()->change();
            });
        }

        if (Schema::hasTable('inventariolineas') && ! Schema::hasColumn('inventariolineas', 'MesDePago')) {
            Schema::table('inventariolineas', function (Blueprint $table) {
                $table->string('MesDePago', 191)->nullable();
            });
        }

        $todos = implode(',', PagoMeses::MESES);
        $vacios = function ($q) {
            $q->whereNull('MesDePago')
                ->orWhere('MesDePago', '')
                ->orWhere('MesDePago', 'N/A');
        };

        if (Schema::hasColumn('inventarioinsumo', 'MesDePago')) {
            DB::table('inventarioinsumo')->where($vacios)->update(['MesDePago' => $todos]);
        }

        if (Schema::hasColumn('inventariolineas', 'MesDePago')) {
            DB::table('inventariolineas')->where($vacios)->update(['MesDePago' => $todos]);
        }
    }

    public function down(): void
    {
        // No se revierte el backfill.
    }
};
