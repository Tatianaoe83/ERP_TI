<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La cadena de TI queda lineal: división → unidad de negocio.
     * Las unidades que ya existían cuelgan de una división, para no dejarlas sueltas.
     */
    public function up(): void
    {
        Schema::create('divisiones', function (Blueprint $table) {
            $table->integer('DivisionID', true);
            $table->string('NombreDivision', 100);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('unidadesdenegocio', function (Blueprint $table) {
            $table->integer('DivisionID')->nullable()->after('UnidadNegocioID');
        });

        $divisionId = DB::table('divisiones')->insertGetId([
            'NombreDivision' => 'CORPORATIVO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('unidadesdenegocio')->update(['DivisionID' => $divisionId]);

        Schema::table('unidadesdenegocio', function (Blueprint $table) {
            $table->integer('DivisionID')->nullable(false)->change();
            $table->foreign('DivisionID')->references('DivisionID')->on('divisiones');
        });
    }

    public function down(): void
    {
        Schema::table('unidadesdenegocio', function (Blueprint $table) {
            $table->dropForeign(['DivisionID']);
            $table->dropColumn('DivisionID');
        });

        Schema::dropIfExists('divisiones');
    }
};
