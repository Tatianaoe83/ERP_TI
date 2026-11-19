<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cortes', function (Blueprint $table) {
            $table->bigIncrements('CortesID');
            $table->string('NombreInsumo', 100)->nullable();
            $table->string('Mes', 100)->nullable();
            $table->year('Anio')->nullable();
            $table->decimal('Costo', 10)->nullable();
            $table->decimal('CostoTotal', 10)->nullable();
            $table->decimal('Margen', 10)->nullable();
            $table->integer('GerenciaID')->nullable();
            $table->unsignedInteger('PresupuestoInternoID')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->softDeletes();

            $table->index(['GerenciaID', 'Anio']);
            $table->index(['PresupuestoInternoID', 'Anio']);
        });

        Schema::create('facturas', function (Blueprint $table) {
            $table->bigIncrements('FacturasID');
            $table->string('Nombre', 300)->nullable();
            $table->unsignedBigInteger('SolicitudID')->nullable();
            $table->unsignedBigInteger('CotizacionID')->nullable();
            $table->unsignedBigInteger('GerenciaID')->nullable();
            $table->unsignedInteger('PresupuestoInternoID')->nullable()->index('facturas_presupuestointernoid_foreign');
            $table->decimal('Importe', 14)->nullable();
            $table->decimal('Costo', 14)->nullable();
            $table->unsignedTinyInteger('Mes')->nullable();
            $table->unsignedSmallInteger('Anio')->nullable();
            $table->unsignedInteger('InsumoID')->nullable();
            $table->string('InsumoNombre', 150)->nullable();
            $table->integer('EmpleadoID')->nullable();
            $table->string('ArchivoRuta', 500)->nullable();
            $table->string('PdfRuta', 500)->nullable();
            $table->string('UUID', 36)->nullable();
            $table->string('Emisor', 300)->nullable()->comment('Razón social emisor');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->softDeletes();
        });

        // Llaves foraneas
        Schema::table('cortes', function (Blueprint $table) {
            $table->foreign(['GerenciaID'])->references(['GerenciaID'])->on('gerencia')->onDelete('SET NULL');
            $table->foreign(['PresupuestoInternoID'])->references(['PresupuestoInternoID'])->on('presupuestos_internos')->onDelete('SET NULL');
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->foreign(['PresupuestoInternoID'])->references(['PresupuestoInternoID'])->on('presupuestos_internos')->onDelete('SET NULL');
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('facturas');
        Schema::dropIfExists('cortes');
        Schema::enableForeignKeyConstraints();
    }
};
