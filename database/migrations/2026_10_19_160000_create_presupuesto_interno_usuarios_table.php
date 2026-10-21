<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePresupuestoInternoUsuariosTable extends Migration
{
    public function up()
    {
        Schema::create('presupuesto_interno_usuarios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('PresupuestoInternoID');
            $table->bigInteger('users_id');
            $table->timestamps();

            $table->foreign('PresupuestoInternoID')
                ->references('PresupuestoInternoID')
                ->on('presupuestos_internos')
                ->cascadeOnDelete();
            $table->foreign('users_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->unique(['PresupuestoInternoID', 'users_id'], 'presupuesto_usuario_unico');
        });
    }

    public function down()
    {
        Schema::dropIfExists('presupuesto_interno_usuarios');
    }
}
