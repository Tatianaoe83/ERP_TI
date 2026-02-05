<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departamento_requerimientos', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->enum('categoria', [
                'Productos base',
                'Programas Especiales',
                'Carpetas',
                'Escaner',
                'Impresora',
            ]);

            $table->string('nombre', 150);
            $table->integer('DepartamentoID');
            $table->boolean('seleccionado')->default(false);
            $table->boolean('realizado')->default(false);
            $table->boolean('opcional')->default(false);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['DepartamentoID', 'nombre'], 'uq_depto_nombre');

            $table->index(['DepartamentoID', 'seleccionado'], 'idx_depto_seleccionado');
            $table->index(['DepartamentoID', 'realizado'], 'idx_depto_realizado');

            $table->foreign('DepartamentoID')
                ->references('DepartamentoID')
                ->on('departamentos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departamento_requerimientos');
    }
};
