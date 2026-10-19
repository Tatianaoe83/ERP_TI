<?php

namespace Database\Seeders;

use App\Helpers\PresupuestoAsignacion;
use App\Models\Insumos;
use App\Models\InventarioInsumo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeederErpNeodata extends Seeder
{
    public function run()
    {
        $neodata2 = Insumos::where('NombreInsumo', 'ERP NEODATA 2')->first();

        if (!$neodata2) {
            $this->command->error('No existe el insumo ERP NEODATA 2 en el catálogo, no se hizo nada.');
            return;
        }

        // Solo empleados físicos activos
        $asignaciones = InventarioInsumo::where('NombreInsumo', 'ERP NEODATA')
            ->whereNull('deleted_at')
            ->whereHas('empleados', function ($q) {
                $q->where('Estado', 1)->where('tipo_persona', 'FISICA');
            })
            ->get();

        $creados = 0;

        DB::transaction(function () use ($asignaciones, $neodata2, &$creados) {
            foreach ($asignaciones as $asignacion) {
                $asignacion->update([
                    'FrecuenciaDePago' => 'Pago único',
                    'MesDePago'        => 'Febrero',
                    'Presupuestado'    => PresupuestoAsignacion::COMPARTIDO,
                ]);

                $nuevo = InventarioInsumo::whereNull('deleted_at')->firstOrCreate(
                    ['EmpleadoID' => $asignacion->EmpleadoID, 'InsumoID' => $neodata2->ID],
                    [
                        'CateogoriaInsumo' => 'LICENCIA',
                        'NombreInsumo'     => $neodata2->NombreInsumo,
                        'CostoMensual'     => $neodata2->CostoMensual,
                        'CostoAnual'       => $neodata2->CostoAnual,
                        'FrecuenciaDePago' => 'Pago único',
                        'MesDePago'        => 'Julio',
                        'Observaciones'    => $neodata2->Observaciones,
                        'FechaAsignacion'  => now()->toDateString(),
                        'NumSerie'         => 'N/A',
                        'Presupuestado'    => PresupuestoAsignacion::COMPARTIDO,
                    ]
                );

                if ($nuevo->wasRecentlyCreated) {
                    $creados++;
                }
            }

            // Los ERP NEODATA 2 que ya existían también quedan en Julio y compartidos
            InventarioInsumo::where('InsumoID', $neodata2->ID)
                ->whereNull('deleted_at')
                ->update([
                    'FrecuenciaDePago' => 'Pago único',
                    'MesDePago'        => 'Julio',
                    'Presupuestado'    => PresupuestoAsignacion::COMPARTIDO,
                ]);
        });

        $this->command->info("ERP NEODATA actualizados a Febrero: {$asignaciones->count()}");
        $this->command->info("ERP NEODATA 2 creados en Julio: {$creados}");
    }
}
