<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSolicitudesMovimientoErp extends Migration
{
    public function up()
    {
        Schema::create('cargos_movimiento', function (Blueprint $table) {
            $table->increments('CargoID');
            $table->string('Clave', 40);
            $table->text('Descripcion');
            $table->string('UnidadNegocio', 80);
            $table->string('Autoriza', 40);
            $table->timestamps();
        });

        $ahora = now();
        $cargos = [
            ['DESVIACION', 'Clave para referenciar una desviación en los presupuestos de las gerencias. Es el homólogo del cargo a obra en los proyectos de construcción.', 'CORPORATIVO', 'jefe_costos'],
            ['ADELANTO', 'Adelanto presupuestal: se adelanta un importe de meses posteriores. Antes hay que disminuir el presupuesto del insumo en el mes de origen.', 'CORPORATIVO', 'jefe_costos'],
            ['OBRA', 'Desviación del presupuesto original con cargo al equipo de obra.', 'TODAS', 'jefe_costos'],
            ['NC OBRA', 'Notas de crédito de proveedores. Aumenta el insumo de la nota de crédito.', 'TODAS', 'jefe_costos'],
            ['COMPRAS', 'La desviación es responsabilidad del equipo de compras.', 'TODAS', 'gerente_compras'],
            ['CONCURSO', 'La desviación viene de una estrategia fallida en la fase de concurso, sin atribuirla al departamento de presupuestos.', 'TODAS', 'gerente_presupuestos'],
            ['PRESUPUESTOS', 'Desviación por omisiones o errores del departamento de presupuestos.', 'TODAS', 'gerente_presupuestos'],
            ['CONFIRMING', 'Aumentos de intereses por confirming.', 'TODAS', 'jefe_costos'],
            ['RECLASIFICACION', 'Traspaso de saldos de un insumo a otro. No combina costo directo con indirecto.', 'TODAS', 'jefe_costos'],
            ['TC RECLASIFICACION', 'Reclasificación del mismo insumo a otra moneda.', 'TODAS', 'jefe_costos'],
            ['OT ##', 'Aumento por orden de trabajo del cliente. La orden debe estar aprobada; si no, solo se alta el 70% del importe.', 'TODAS', 'jefe_costos'],
            ['GARANTIAS', 'Trabajos de garantía. La solicitud debe traer el folio de garantía.', 'TODAS', 'jefe_costos'],
            ['AJUSTE', 'Movimiento puntual: reclasificación entre proyectos o una corrección.', 'TODAS', 'jefe_costos'],
            ['DESTAJO', 'Desviación por captura de destajos y quincenas. Se trata como cargo a obra hasta indicar el cargo correcto.', 'TODAS', 'jefe_costos'],
            ['SUELDO', 'Desviación por captura de sueldos, hasta indicar el cargo correcto.', 'INDUSTRIAL/CORPORATIVO/GERENCIAS', 'jefe_costos'],
            ['FA', 'Aumentos por FA a los insumos de este registro.', 'TODAS', 'jefe_costos'],
            ['CIERRE', 'Se aplica una vez por insumo para reflejar el cierre de precios de compras.', 'TODAS', 'gerente_compras'],
            ['PLANTA', 'Homólogo del cargo a obra para las unidades industriales.', 'KONKRET/AGREGADOS', 'jefe_costos'],
            ['CSP', 'Concretos sobre pedido. Homólogo de la orden de trabajo para unidades industriales.', 'KONKRET/AGREGADOS', 'jefe_costos'],
            ['CAJAS', 'Consumos de caja chica. Se quita del insumo consumido y se pasa a un insumo de caja.', 'TODAS', 'jefe_costos'],
            ['PRV', 'Movimientos previos a tener el presupuesto de control del proyecto.', 'KONKRET/AGREGADOS', 'jefe_costos'],
            ['GP', 'Aumentos por GP estrategias.', 'TODAS', 'jefe_costos'],
            ['ALCANCE', 'Cambio del presupuesto original por reducción del alcance de la obra.', 'TODAS', 'jefe_costos'],
            ['PROYECTOS', 'Cargo por consideraciones erróneas del departamento de proyectos.', 'TODAS', 'director_proyectos'],
        ];

        foreach ($cargos as $cargo) {
            DB::table('cargos_movimiento')->insert([
                'Clave' => $cargo[0],
                'Descripcion' => $cargo[1],
                'UnidadNegocio' => $cargo[2],
                'Autoriza' => $cargo[3],
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        Schema::create('solicitudes_movimiento', function (Blueprint $table) {
            $table->increments('SolicitudMovimientoID');
            $table->unsignedInteger('EmpleadoID');
            $table->unsignedInteger('ObraID');
            $table->unsignedInteger('CargoID');
            $table->unsignedInteger('ResidenteControlID');
            $table->unsignedInteger('AutorizadorID')->nullable();
            $table->string('AutorizadorRol', 80);
            $table->text('Justificacion');
            $table->string('Estatus', 30)->default('Por autorizar');
            $table->string('Folio', 40)->nullable();
            $table->timestamp('autorizado_en')->nullable();
            $table->timestamp('aplicado_en')->nullable();
            $table->timestamps();

            $table->unique('Folio');
            $table->index('Estatus');
        });

        Schema::create('solicitud_movimiento_archivos', function (Blueprint $table) {
            $table->increments('ArchivoID');
            $table->unsignedInteger('SolicitudMovimientoID');
            $table->string('Ruta', 255);
            $table->string('Nombre', 180);
            $table->string('Tipo', 20)->default('soporte');
            $table->timestamps();
        });

        Schema::create('solicitud_movimiento_comentarios', function (Blueprint $table) {
            $table->increments('ComentarioID');
            $table->unsignedInteger('SolicitudMovimientoID');
            $table->unsignedInteger('UserID')->nullable();
            $table->string('Autor', 150);
            $table->text('Texto');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('solicitud_movimiento_comentarios');
        Schema::dropIfExists('solicitud_movimiento_archivos');
        Schema::dropIfExists('solicitudes_movimiento');
        Schema::dropIfExists('cargos_movimiento');
    }
}
