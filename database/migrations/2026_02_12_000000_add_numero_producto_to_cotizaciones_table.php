<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNumeroProductoToCotizacionesTable extends Migration
{
    public function up()
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->integer('NumeroProducto')->default(1)->after('NumeroPropuesta')->comment('Identificador de producto dentro de la propuesta. Permite múltiples productos por propuesta.');
        });

        DB::statement("
            UPDATE cotizaciones c1
            SET NumeroProducto = (
                SELECT COUNT(DISTINCT CONCAT(COALESCE(c2.NombreEquipo, ''), '|', COALESCE(c2.Descripcion, '')))
                FROM cotizaciones c2
                WHERE c2.SolicitudID = c1.SolicitudID 
                AND c2.NumeroPropuesta = c1.NumeroPropuesta
                AND (
                    CONCAT(COALESCE(c2.NombreEquipo, ''), '|', COALESCE(c2.Descripcion, '')) < CONCAT(COALESCE(c1.NombreEquipo, ''), '|', COALESCE(c1.Descripcion, ''))
                    OR (
                        CONCAT(COALESCE(c2.NombreEquipo, ''), '|', COALESCE(c2.Descripcion, '')) = CONCAT(COALESCE(c1.NombreEquipo, ''), '|', COALESCE(c1.Descripcion, ''))
                        AND c2.CotizacionID <= c1.CotizacionID
                    )
                )
            )
        ");
    }

    public function down()
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn('NumeroProducto');
        });
    }
}
