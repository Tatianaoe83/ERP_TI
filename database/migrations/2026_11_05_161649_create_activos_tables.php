<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('companiaslineastelefonicas', function (Blueprint $table) {
            $table->integer('ID', true);
            $table->string('Compania', 50);
        });

        Schema::create('lineastelefonicas', function (Blueprint $table) {
            $table->integer('LineaID', true);
            $table->string('NumTelefonico', 50)->unique('UQ_LineasTelefonicas_NumTelefonico');
            $table->integer('PlanID');
            $table->string('CuentaPadre', 100);
            $table->string('CuentaHija', 100);
            $table->string('TipoLinea', 50);
            $table->integer('ObraID')->index('FK_LineasTelefonicas_ObraID');
            $table->date('FechaFianza')->nullable();
            $table->decimal('CostoFianza', 10);
            $table->boolean('Activo');
            $table->boolean('Disponible')->default(true);
            $table->decimal('MontoRenovacionFianza', 10)->nullable();
            $table->date('FechaRenovacion')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('equipos', function (Blueprint $table) {
            $table->integer('ID', true);
            $table->integer('CategoriaID')->index('FK__Equipos__Categor__29221CFB');
            $table->string('Marca', 150);
            $table->string('Caracteristicas', 255);
            $table->string('Modelo', 100);
            $table->decimal('Precio', 10);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('insumos', function (Blueprint $table) {
            $table->integer('ID', true);
            $table->string('NombreInsumo', 100);
            $table->integer('CategoriaID')->index('FK__Insumos__Categor__2DE6D218');
            $table->decimal('CostoMensual', 10);
            $table->decimal('CostoAnual', 10);
            $table->decimal('Importe', 10, 4)->default(0);
            $table->string('FrecuenciaDePago', 50)->nullable();
            $table->string('Observaciones', 255)->nullable();
            $table->date('FechaRenovacion')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('tiposdecategorias', function (Blueprint $table) {
            $table->integer('ID', true);
            $table->string('Categoria', 50)->nullable();
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->integer('ID', true);
            $table->integer('TipoID')->index('FK__Categoria__TipoI__2645B050');
            $table->string('Categoria', 75);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('planes', function (Blueprint $table) {
            $table->integer('ID', true);
            $table->integer('CompaniaID');
            $table->string('NombrePlan', 50);
            $table->decimal('PrecioPlan', 10);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('planes');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('tiposdecategorias');
        Schema::dropIfExists('insumos');
        Schema::dropIfExists('equipos');
        Schema::dropIfExists('lineastelefonicas');
        Schema::dropIfExists('companiaslineastelefonicas');
        Schema::enableForeignKeyConstraints();
    }
};
