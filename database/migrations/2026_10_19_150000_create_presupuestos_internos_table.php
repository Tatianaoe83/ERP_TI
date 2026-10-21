<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePresupuestosInternosTable extends Migration
{
    public function up()
    {
        Schema::create('presupuestos_internos', function (Blueprint $table) {
            $table->increments('PresupuestoInternoID');
            $table->string('Nombre', 150);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->unsignedInteger('PresupuestoInternoID')->nullable()->after('CentroCostoID');
            $table->foreign('PresupuestoInternoID')
                ->references('PresupuestoInternoID')
                ->on('presupuestos_internos')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropForeign(['PresupuestoInternoID']);
            $table->dropColumn('PresupuestoInternoID');
        });

        Schema::dropIfExists('presupuestos_internos');
    }
}
