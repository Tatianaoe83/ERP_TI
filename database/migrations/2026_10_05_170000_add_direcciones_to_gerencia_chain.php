<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La cadena de TI queda lineal: unidad → dirección → gerencia.
     * Cada gerencia que ya existía cuelga de una dirección de su unidad,
     * para no dejar registros a la mitad.
     */
    public function up(): void
    {
        Schema::create('direcciones', function (Blueprint $table) {
            $table->integer('DireccionID', true);
            $table->string('NombreDireccion', 100);
            $table->integer('UnidadNegocioID');
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('UnidadNegocioID')->references('UnidadNegocioID')->on('unidadesdenegocio');
        });

        Schema::table('gerencia', function (Blueprint $table) {
            $table->integer('DireccionID')->nullable()->after('UnidadNegocioID');
        });

        $unidades = DB::table('unidadesdenegocio')->orderBy('UnidadNegocioID')->get();

        foreach ($unidades as $unidad) {
            $direccionId = DB::table('direcciones')->insertGetId([
                'NombreDireccion' => $unidad->NombreEmpresa,
                'UnidadNegocioID' => $unidad->UnidadNegocioID,
                'estado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('gerencia')
                ->where('UnidadNegocioID', $unidad->UnidadNegocioID)
                ->update(['DireccionID' => $direccionId]);
        }

        Schema::table('gerencia', function (Blueprint $table) {
            $table->integer('DireccionID')->nullable(false)->change();
            $table->foreign('DireccionID')->references('DireccionID')->on('direcciones');
        });
    }

    public function down(): void
    {
        Schema::table('gerencia', function (Blueprint $table) {
            $table->dropForeign(['DireccionID']);
            $table->dropColumn('DireccionID');
        });

        Schema::dropIfExists('direcciones');
    }
};
