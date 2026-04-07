<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCorteidToFacturas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropForeign('facturas_insumoid_foreign');
        });
    }
 
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->foreign('InsumoID', 'facturas_insumoid_foreign')
                ->references('ID')->on('insumos')->onDelete('set null');
        });
    }
}
