<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddEquipoPropioToInventarioEquipos extends Migration
{
    /**
     * "Presupuestado" era un booleano (stock / extra). Ahora el equipo tiene tres
     * modalidades, así que la columna pasa a llamarse "tipoEquipo":
     *   0 = No presupuestado (stock de la empresa)
     *   1 = Presupuestado (extra / proyección futura)
     *   2 = Propio (equipo del empleado; se lista junto al stock)
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('inventarioequipo', 'Presupuestado')) {
            // Paso intermedio a VARCHAR: MySQL convierte TINYINT -> ENUM por *índice*
            // (el 0 no existe como índice y truncaría los datos), pero VARCHAR -> ENUM
            // sí empareja por valor.
            DB::statement("ALTER TABLE inventarioequipo CHANGE Presupuestado tipoEquipo VARCHAR(1) NOT NULL DEFAULT '0'");
        }

        if (Schema::hasColumn('inventarioequipo', 'tipoEquipo')) {
            DB::statement("ALTER TABLE inventarioequipo MODIFY tipoEquipo ENUM('0', '1', '2') NOT NULL DEFAULT '0'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasColumn('inventarioequipo', 'tipoEquipo')) {
            return;
        }

        // 'Propio' no existe en el esquema viejo: regresa a stock (0).
        DB::statement("UPDATE inventarioequipo SET tipoEquipo = '0' WHERE tipoEquipo = '2'");
        DB::statement("ALTER TABLE inventarioequipo MODIFY tipoEquipo VARCHAR(1) NOT NULL DEFAULT '0'");
        DB::statement("ALTER TABLE inventarioequipo CHANGE tipoEquipo Presupuestado TINYINT(1) NOT NULL DEFAULT 0");
    }
}
