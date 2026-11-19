<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('departamentos', 'DireccionID')) {
            Schema::table('departamentos', function (Blueprint $table) {
                $table->integer('DireccionID')->nullable()->index('departamentos_direccionid_index')->after('GerenciaID');
            });
        }

        if (!Schema::hasTable('direccion_unidad_negocio')) {
            Schema::create('direccion_unidad_negocio', function (Blueprint $table) {
                $table->integer('DireccionID');
                $table->integer('UnidadNegocioID');
                $table->timestamps();
                $table->primary(['DireccionID', 'UnidadNegocioID'], 'direccion_unidad_negocio_primary');
                $table->foreign(['DireccionID'])->references(['DireccionID'])->on('direcciones')->onDelete('cascade');
                $table->foreign(['UnidadNegocioID'])->references(['UnidadNegocioID'])->on('unidadesdenegocio')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('direccion_unidad_negocio');

        if (Schema::hasColumn('departamentos', 'DireccionID')) {
            Schema::table('departamentos', function (Blueprint $table) {
                $table->dropColumn('DireccionID');
            });
        }
    }
};
