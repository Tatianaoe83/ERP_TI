<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {

            $table->bigIncrements('FacturasID');

            $table->string('Nombre', 300)->nullable();

            $table->unsignedBigInteger('SolicitudID')->nullable();

            $table->decimal('Importe', 14, 2)->nullable();

            $table->decimal('Costo', 14, 2)->nullable();

            $table->tinyInteger('Mes')->unsigned()->nullable();

            $table->smallInteger('Anio')->unsigned()->nullable();

            // 🔹 Relación con insumos
            $table->unsignedInteger('InsumoID')->nullable();

            $table->string('ArchivoRuta', 500)->nullable();

            $table->string('PdfRuta', 500)->nullable();

            $table->string('UUID', 36)->nullable();

            $table->string('Emisor', 300)->nullable()->comment('Razón social emisor');

            $table->timestamp('created_at')->useCurrent();
            
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->softDeletes();

            // 🔹 Foreign key
            $table->foreign('InsumoID')
                  ->references('ID')
                  ->on('insumos')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};