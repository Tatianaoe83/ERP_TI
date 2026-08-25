<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventariolineas') || Schema::hasColumn('inventariolineas', 'PlanID')) {
            return;
        }

        Schema::table('inventariolineas', function (Blueprint $table) {
            $table->unsignedInteger('PlanID')->nullable()->after('PlanTel');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('inventariolineas', 'PlanID')) {
            return;
        }

        Schema::table('inventariolineas', function (Blueprint $table) {
            $table->dropColumn('PlanID');
        });
    }
};
