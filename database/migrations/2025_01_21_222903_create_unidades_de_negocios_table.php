<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnidadesDeNegociosTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unidades_de_negocios', function (Blueprint $table) {
            $table->id('id');
            $table->varchar('NombreEscuela');
            $table->varchar('RFC');
            $table->varchar('Direccion');
            $table->varchar('NumTelefono');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('unidades_de_negocios');
    }
}
