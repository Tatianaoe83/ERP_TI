<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventarioequipo', function (Blueprint $table) {
            $table->integer('InventarioID', true);
            $table->integer('EmpleadoID')->index('FK__Inventari__Emple__3864608B');
            $table->string('CategoriaEquipo', 150);
            $table->integer('GerenciaEquipoID')->nullable();
            $table->string('Marca', 150);
            $table->string('Caracteristicas', 255);
            $table->string('Modelo', 100);
            $table->decimal('Precio', 10)->nullable();
            $table->date('FechaAsignacion')->nullable();
            $table->string('NumSerie', 100)->nullable();
            $table->string('Folio', 50)->nullable();
            $table->string('GerenciaEquipo', 100)->nullable();
            $table->string('Comentarios', 400)->nullable();
            $table->date('FechaDeCompra')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->enum('tipoEquipo', ['0', '1', '2', '3'])->default('0')->index('inventarioequipo_presupuestado_index');
            $table->string('MesDePago')->nullable()->index();
        });

        Schema::create('inventarioinsumo', function (Blueprint $table) {
            $table->integer('InventarioID', true);
            $table->integer('EmpleadoID')->index('FK__Inventari__Emple__3B40CD36');
            $table->integer('InsumoID')->nullable()->index('FK__Inventari__Insum__3C34F16F');
            $table->string('CateogoriaInsumo', 50);
            $table->string('NombreInsumo', 100);
            $table->decimal('CostoMensual', 10);
            $table->decimal('CostoAnual', 10);
            $table->string('FrecuenciaDePago', 50);
            $table->date('FechaRenovacion')->nullable();
            $table->string('Observaciones', 255)->nullable();
            $table->date('FechaAsignacion')->nullable();
            $table->string('NumSerie', 100)->nullable();
            $table->string('Comentarios', 400)->nullable();
            $table->string('MesDePago')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
            $table->boolean('Presupuestado')->default(false)->index();
        });

        Schema::create('inventariolineas', function (Blueprint $table) {
            $table->integer('InventarioID', true);
            $table->integer('EmpleadoID')->index('FK__Inventari__Emple__3F115E1A');
            $table->string('NumTelefonico', 50)->nullable();
            $table->string('Compania', 100);
            $table->string('PlanTel', 50);
            $table->unsignedInteger('PlanID')->nullable();
            $table->decimal('CostoRentaMensual', 10);
            $table->string('CuentaPadre', 100)->nullable();
            $table->string('CuentaHija', 100)->nullable();
            $table->string('TipoLinea', 50);
            $table->integer('ObraID')->index('FK__Inventari__ObraI__40058253');
            $table->string('Obra', 100);
            $table->date('FechaFianza')->nullable();
            $table->decimal('CostoFianza', 10)->nullable();
            $table->date('FechaAsignacion')->nullable();
            $table->string('Estado', 50);
            $table->string('Comentarios', 400)->nullable();
            $table->decimal('MontoRenovacionFianza', 10)->nullable();
            $table->date('FechaRenovacion')->nullable();
            $table->integer('LineaID')->nullable()->index('FK_InventarioLineas_LineasTelefonicas');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
            $table->boolean('Presupuestado')->default(false)->index();
            $table->string('FrecuenciaDePago', 40)->nullable();
            $table->string('MesDePago')->nullable();
        });

        Schema::create('auditorias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('Folio', 30)->unique();
            $table->unsignedBigInteger('id_empleado')->nullable();
            $table->string('generada_por_nombre', 150)->nullable();
            $table->integer('EmpleadoID')->nullable()->index();
            $table->unsignedTinyInteger('tipoEquipo')->nullable();
            $table->json('licencias_auditadas')->nullable();
            $table->unsignedSmallInteger('total_licencias_auditadas')->default(0);
            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('auditorias_equipos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('auditoria_id');
            $table->integer('InventarioID')->index();
            $table->string('CategoriaEquipo', 100)->nullable();
            $table->string('Marca', 150)->nullable();
            $table->string('Modelo', 100)->nullable();
            $table->string('NumSerie', 100)->nullable();
            $table->string('Folio', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['auditoria_id', 'InventarioID'], 'auditoria_equipo_unico');
        });

        Schema::create('auditorias_licencias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('auditoria_id');
            $table->string('NombreLicencia', 255);
            $table->boolean('tiene_licencia')->default(false)->index();
            $table->boolean('original')->nullable()->index();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['auditoria_id', 'NombreLicencia'], 'auditoria_licencia_unico');
        });

        // Llaves foraneas
        Schema::table('auditorias_equipos', function (Blueprint $table) {
            $table->foreign(['auditoria_id'])->references(['id'])->on('auditorias')->onDelete('CASCADE');
        });

        Schema::table('auditorias_licencias', function (Blueprint $table) {
            $table->foreign(['auditoria_id'])->references(['id'])->on('auditorias')->onDelete('CASCADE');
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('auditorias_licencias');
        Schema::dropIfExists('auditorias_equipos');
        Schema::dropIfExists('auditorias');
        Schema::dropIfExists('inventariolineas');
        Schema::dropIfExists('inventarioinsumo');
        Schema::dropIfExists('inventarioequipo');
        Schema::enableForeignKeyConstraints();
    }
};
