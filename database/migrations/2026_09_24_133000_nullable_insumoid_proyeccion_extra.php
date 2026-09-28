<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventarioinsumo') || ! Schema::hasColumn('inventarioinsumo', 'InsumoID')) {
            return;
        }

        Schema::table('inventarioinsumo', function (Blueprint $table) {
            $table->integer('InsumoID')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventarioinsumo') || ! Schema::hasColumn('inventarioinsumo', 'InsumoID')) {
            return;
        }

        Schema::table('inventarioinsumo', function (Blueprint $table) {
            $table->integer('InsumoID')->nullable(false)->change();
        });
    }
};
