<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventarioequipo')) {
            return;
        }

        Schema::table('inventarioequipo', function (Blueprint $table) {
            $table->integer('GerenciaEquipoID')->nullable()->change();
            $table->string('GerenciaEquipo', 100)->nullable()->change();
            $table->date('FechaAsignacion')->nullable()->change();
            $table->string('NumSerie', 100)->nullable()->change();
            $table->string('Folio', 50)->nullable()->change();
            $table->decimal('Precio', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventarioequipo')) {
            return;
        }

        Schema::table('inventarioequipo', function (Blueprint $table) {
            $table->integer('GerenciaEquipoID')->nullable(false)->change();
            $table->string('GerenciaEquipo', 100)->nullable(false)->change();
            $table->date('FechaAsignacion')->nullable(false)->change();
            $table->string('NumSerie', 100)->nullable(false)->change();
            $table->string('Folio', 50)->nullable(false)->change();
            $table->decimal('Precio', 10, 2)->nullable(false)->change();
        });
    }
};
