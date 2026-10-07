<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->integer('JefeDirectoID')->nullable()->after('PuestoID');
            $table->foreign('JefeDirectoID')->references('EmpleadoID')->on('empleados')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropForeign(['JefeDirectoID']);
            $table->dropColumn('JefeDirectoID');
        });
    }
};
