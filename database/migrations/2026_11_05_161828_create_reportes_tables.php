<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('presupuesto_configuracion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('grupo', 64)->index();
            $table->string('valor', 150);
            $table->timestamps();

            $table->unique(['grupo', 'valor']);
        });

        Schema::create('gerencias_usuarios', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('users_id')->index('fk_usuarios_08ef174420');
            $table->integer('GerenciaID')->index('fk_gerencia_08ef174420');
            $table->timestamps();
        });

        Schema::create('query_forms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('title')->nullable();
            $table->longText('query_details')->nullable();
            $table->text('password')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('query_forms');
        Schema::dropIfExists('gerencias_usuarios');
        Schema::dropIfExists('presupuesto_configuracion');
        Schema::enableForeignKeyConstraints();
    }
};
