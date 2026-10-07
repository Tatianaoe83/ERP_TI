<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class NullablePuestoObraOnEmpleados extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE empleados MODIFY PuestoID INT NULL');
        DB::statement('ALTER TABLE empleados MODIFY ObraID INT NULL');
    }

    public function down()
    {
        DB::statement('UPDATE empleados SET PuestoID = 0 WHERE PuestoID IS NULL');
        DB::statement('UPDATE empleados SET ObraID = 0 WHERE ObraID IS NULL');
        DB::statement('ALTER TABLE empleados MODIFY PuestoID INT NOT NULL');
        DB::statement('ALTER TABLE empleados MODIFY ObraID INT NOT NULL');
    }
}
