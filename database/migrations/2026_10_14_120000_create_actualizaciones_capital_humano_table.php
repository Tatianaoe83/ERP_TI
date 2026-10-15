<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActualizacionesCapitalHumanoTable extends Migration
{
    public function up()
    {
        Schema::create('actualizaciones_capital_humano', function (Blueprint $table) {
            $table->increments('id');
            $table->string('origen', 30);
            $table->unsignedBigInteger('origen_id');
            $table->string('tipo', 20);
            $table->unsignedInteger('empleado_ti_id')->nullable();
            $table->string('nombre', 150);
            $table->date('fecha')->nullable();
            $table->text('resumen');
            $table->string('estado', 40);
            $table->timestamps();

            $table->unique(['origen', 'origen_id']);
            $table->index('estado');
        });
    }

    public function down()
    {
        Schema::dropIfExists('actualizaciones_capital_humano');
    }
}
