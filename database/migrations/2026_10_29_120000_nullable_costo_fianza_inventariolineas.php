<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Las líneas extra son por comprar: no traen fianza hasta pasarlas a stock.
    public function up(): void
    {
        if (! Schema::hasTable('inventariolineas')) {
            return;
        }

        Schema::table('inventariolineas', function (Blueprint $table) {
            $table->decimal('CostoFianza', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventariolineas')) {
            return;
        }

        Schema::table('inventariolineas', function (Blueprint $table) {
            $table->decimal('CostoFianza', 10, 2)->nullable(false)->change();
        });
    }
};
