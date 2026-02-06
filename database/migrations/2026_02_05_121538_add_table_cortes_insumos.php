<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cortes', function (Blueprint $table) {
            $table->id('CortesID');

            $table->string('NombreInsumo', 100)->nullable();
            $table->string('Mes', 100)->nullable();
            $table->year('Anio')->nullable();

            $table->decimal('Costo', 10, 2)->nullable();
            $table->decimal('CostoTotal', 10, 2)->nullable();
            $table->decimal('Margen', 10, 2)->nullable();

            $table->integer('GerenciaID')->nullable();

            $table->foreign('GerenciaID')
                ->references('GerenciaID')
                ->on('gerencia')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();

            $table->index(['GerenciaID', 'Anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cortes');
    }
};
