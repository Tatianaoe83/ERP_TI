<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('inventarioinsumo', 'LicenciaPirata')) {
            return;
        }

        Schema::table('inventarioinsumo', function (Blueprint $table) {
            $table->boolean('LicenciaPirata')->default(false)->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('inventarioinsumo', 'LicenciaPirata')) {
            return;
        }

        Schema::table('inventarioinsumo', function (Blueprint $table) {
            $table->dropColumn('LicenciaPirata');
        });
    }
};
