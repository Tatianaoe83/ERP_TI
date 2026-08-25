<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('auditorias', 'InventarioID')) {
            Schema::table('auditorias', function (Blueprint $table) {
                $table->dropIndex(['InventarioID']);
                $table->dropColumn('InventarioID');
            });
        }

        Schema::dropIfExists('auditorias_licencias');
        Schema::dropIfExists('auditorias_equipos');

        Schema::create('auditorias_equipos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('auditoria_id');
            $table->integer('InventarioID')->index();

            $table->boolean('presente')->nullable()->default(null);
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->unique(['auditoria_id', 'InventarioID'], 'auditoria_equipo_unico');
            $table->index('presente');
            $table->foreign('auditoria_id')->references('id')->on('auditorias')->onDelete('cascade');
        });

        Schema::create('auditorias_licencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('auditoria_id');
            $table->string('NombreLicencia', 255);

            $table->boolean('tiene_licencia')->default(false);
            $table->boolean('original')->nullable()->default(null);
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->unique(['auditoria_id', 'NombreLicencia'], 'auditoria_licencia_unico');
            $table->index('original');
            $table->index('tiene_licencia');
            $table->foreign('auditoria_id')->references('id')->on('auditorias')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias_licencias');
        Schema::dropIfExists('auditorias_equipos');

        Schema::create('auditorias_equipos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('auditoria_id');
            $table->string('NombreLicencia', 255)->nullable();
            $table->boolean('tiene_licencia')->default(false);
            $table->boolean('original')->nullable()->default(null);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['auditoria_id', 'NombreLicencia'], 'auditoria_licencia_unico');
            $table->foreign('auditoria_id')->references('id')->on('auditorias')->onDelete('cascade');
        });

        Schema::table('auditorias', function (Blueprint $table) {
            $table->integer('InventarioID')->nullable()->after('EmpleadoID')->index();
        });
    }
};
