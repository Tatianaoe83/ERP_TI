<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCotizacionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id('CotizacionID');
            $table->integer('SolicitudID');
            $table->string('Proveedor', 255);
            $table->text('Descripcion');
            $table->decimal('Precio', 10, 2);
            $table->integer('TiempoEntrega')->nullable()->comment('Días');
            $table->text('Observaciones')->nullable();
            $table->enum('Estatus', ['Pendiente', 'Seleccionada', 'Rechazada'])->default('Pendiente');
            $table->integer('NumeroPropuesta')->comment('1, 2 o 3');
            $table->timestamps();
            
            $table->foreign('SolicitudID')->references('SolicitudID')->on('solicitudes')->onDelete('cascade');
            $table->index('SolicitudID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cotizaciones');
    }
}
