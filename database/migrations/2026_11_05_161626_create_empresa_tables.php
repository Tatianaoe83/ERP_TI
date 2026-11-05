<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('divisiones', function (Blueprint $table) {
            $table->integer('DivisionID', true);
            $table->string('NombreDivision', 100);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unidadesdenegocio', function (Blueprint $table) {
            $table->integer('UnidadNegocioID', true);
            $table->integer('DivisionID')->index('unidadesdenegocio_divisionid_foreign');
            $table->string('NombreEmpresa', 100);
            $table->string('RFC', 100);
            $table->string('Direccion', 150);
            $table->string('NumTelefono', 100);
            $table->boolean('estado')->default(true);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('direcciones', function (Blueprint $table) {
            $table->integer('DireccionID', true);
            $table->string('NombreDireccion', 100);
            $table->integer('UnidadNegocioID')->index('direcciones_unidadnegocioid_foreign');
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gerencia', function (Blueprint $table) {
            $table->integer('GerenciaID', true);
            $table->string('NombreGerencia', 100);
            $table->integer('UnidadNegocioID')->nullable()->index('FK__Gerencia__Unidad__02FC7413');
            $table->integer('DireccionID')->index('gerencia_direccionid_foreign');
            $table->string('NombreGerente', 100)->nullable();
            $table->boolean('estado')->default(true);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('departamentos', function (Blueprint $table) {
            $table->integer('DepartamentoID', true);
            $table->string('NombreDepartamento', 50)->nullable();
            $table->integer('GerenciaID')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('puestos', function (Blueprint $table) {
            $table->integer('PuestoID', true);
            $table->string('NombrePuesto', 75)->nullable();
            $table->integer('DepartamentoID')->nullable()->index('FK__Puestos__Departa__0D7A0286');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('obras', function (Blueprint $table) {
            $table->integer('ObraID', true);
            $table->string('NombreObra', 100);
            $table->string('Direccion', 150);
            $table->string('EncargadoDeObra', 150);
            $table->integer('UnidadNegocioID')->index('FK__Obras__UnidadNeg__08B54D69');
            $table->boolean('estado')->default(true);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('proyectos', function (Blueprint $table) {
            $table->integer('ProyectoID');
            $table->string('Proyecto', 100);
            $table->string('NombreProyecto', 250);
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('centrosdecostos', function (Blueprint $table) {
            $table->increments('CentroCostoID');
            $table->string('NombreCentro', 150);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('presupuestos_internos', function (Blueprint $table) {
            $table->increments('PresupuestoInternoID');
            $table->string('Nombre', 150);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('presupuesto_interno_usuarios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('PresupuestoInternoID');
            $table->bigInteger('users_id')->index('presupuesto_interno_usuarios_users_id_foreign');
            $table->timestamps();

            $table->unique(['PresupuestoInternoID', 'users_id'], 'presupuesto_usuario_unico');
        });

        Schema::create('empleados', function (Blueprint $table) {
            $table->integer('EmpleadoID', true);
            $table->string('NombreEmpleado', 100);
            $table->integer('PuestoID')->nullable()->index('FK__Empleados__Puest__114A936A');
            $table->integer('JefeDirectoID')->nullable()->index('empleados_jefedirectoid_foreign');
            $table->integer('ObraID')->nullable()->index('FK__Empleados__ObraI__10566F31');
            $table->unsignedInteger('CentroCostoID')->nullable()->index('empleados_centrocostoid_foreign');
            $table->unsignedInteger('PresupuestoInternoID')->nullable()->index('empleados_presupuestointernoid_foreign');
            $table->string('NumTelefono')->nullable();
            $table->string('Correo')->nullable();
            $table->boolean('Estado');
            $table->enum('tipo_persona', ['FISICA', 'REFERENCIADO', 'EXTRAORDINARIO'])->nullable()->default('FISICA');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->dateTime('deleted_at')->nullable();
        });

        Schema::create('actualizaciones_capital_humano', function (Blueprint $table) {
            $table->increments('id');
            $table->string('origen', 30);
            $table->unsignedBigInteger('origen_id');
            $table->string('tipo', 20);
            $table->unsignedInteger('empleado_ti_id')->nullable();
            $table->string('nombre', 150);
            $table->date('fecha')->nullable();
            $table->text('resumen');
            $table->string('estado', 40)->index();
            $table->timestamps();

            $table->unique(['origen', 'origen_id']);
        });

        // Llaves foraneas
        Schema::table('unidadesdenegocio', function (Blueprint $table) {
            $table->foreign(['DivisionID'])->references(['DivisionID'])->on('divisiones');
        });

        Schema::table('direcciones', function (Blueprint $table) {
            $table->foreign(['UnidadNegocioID'])->references(['UnidadNegocioID'])->on('unidadesdenegocio');
        });

        Schema::table('gerencia', function (Blueprint $table) {
            $table->foreign(['DireccionID'])->references(['DireccionID'])->on('direcciones');
        });

        Schema::table('presupuesto_interno_usuarios', function (Blueprint $table) {
            $table->foreign(['PresupuestoInternoID'])->references(['PresupuestoInternoID'])->on('presupuestos_internos')->onDelete('CASCADE');
            $table->foreign(['users_id'])->references(['id'])->on('users')->onDelete('CASCADE');
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->foreign(['CentroCostoID'])->references(['CentroCostoID'])->on('centrosdecostos')->onDelete('SET NULL');
            $table->foreign(['PresupuestoInternoID'])->references(['PresupuestoInternoID'])->on('presupuestos_internos')->onDelete('SET NULL');
            $table->foreign(['JefeDirectoID'])->references(['EmpleadoID'])->on('empleados')->onDelete('SET NULL');
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('actualizaciones_capital_humano');
        Schema::dropIfExists('empleados');
        Schema::dropIfExists('presupuesto_interno_usuarios');
        Schema::dropIfExists('presupuestos_internos');
        Schema::dropIfExists('centrosdecostos');
        Schema::dropIfExists('proyectos');
        Schema::dropIfExists('obras');
        Schema::dropIfExists('puestos');
        Schema::dropIfExists('departamentos');
        Schema::dropIfExists('gerencia');
        Schema::dropIfExists('direcciones');
        Schema::dropIfExists('unidadesdenegocio');
        Schema::dropIfExists('divisiones');
        Schema::enableForeignKeyConstraints();
    }
};
