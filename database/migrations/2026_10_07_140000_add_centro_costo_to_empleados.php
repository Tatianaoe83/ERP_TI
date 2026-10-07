<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCentroCostoToEmpleados extends Migration
{
    public function up()
    {
        Schema::create('centrosdecostos', function (Blueprint $table) {
            $table->increments('CentroCostoID');
            $table->string('NombreCentro', 150);
            $table->timestamps();
            $table->softDeletes();
        });

        $nombres = [
            'G600 DIRECCION GENERAL 2026',
            'G601 DIRECCION ADMON Y FINANZAS 2026',
            'G601 TI DIRECCION ADM Y FINANZAS 2026',
            'G602 DIR TECNICA CORPORATIVA 2026',
            'G603 DIRECCION DESARROLLO DE NEGOCIOS 26',
            'G604 DIR JURIDICA Y GESTION ESTRATEGICA',
            'G610 GERENCIA DE ADMON Y CH 2026',
            'G620 GERENCIA DE GASTOS COMPARTIDOS 2026',
            'G630 GERENCIA DE FINANZAS 2026',
            'G640 GERENCIA DE CONTROL 2026',
            'G650 GERENCIA DE COMPRAS 2026',
            'G660 GERENCIA DE PRESUPUESTOS 2026',
        ];

        $ahora = now();
        foreach ($nombres as $nombre) {
            DB::table('centrosdecostos')->insert([
                'NombreCentro' => $nombre,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        Schema::table('empleados', function (Blueprint $table) {
            $table->unsignedInteger('CentroCostoID')->nullable()->after('ObraID');
            $table->foreign('CentroCostoID')
                ->references('CentroCostoID')
                ->on('centrosdecostos')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropForeign(['CentroCostoID']);
            $table->dropColumn('CentroCostoID');
        });

        Schema::dropIfExists('centrosdecostos');
    }
}
