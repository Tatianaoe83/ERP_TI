<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('solicitudes_movimiento', function (Blueprint $table) {
            $table->string('aplicado_por', 180)->nullable()->after('aplicado_en');
        });
    }

    public function down()
    {
        Schema::table('solicitudes_movimiento', function (Blueprint $table) {
            $table->dropColumn('aplicado_por');
        });
    }
};
