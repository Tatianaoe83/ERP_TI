<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('solicitudes_movimiento')->where('Estatus', 'Por autorizar')->update(['Estatus' => 'Pendiente aprobación']);
        DB::table('solicitudes_movimiento')->where('Estatus', 'Autorizada')->update(['Estatus' => 'Aprobada']);
    }

    public function down()
    {
        DB::table('solicitudes_movimiento')->where('Estatus', 'Pendiente aprobación')->update(['Estatus' => 'Por autorizar']);
        DB::table('solicitudes_movimiento')->where('Estatus', 'Aprobada')->update(['Estatus' => 'Autorizada']);
    }
};
