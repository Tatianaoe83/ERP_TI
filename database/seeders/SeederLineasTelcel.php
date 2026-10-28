<?php

namespace Database\Seeders;

use App\Models\InventarioLineas;
use App\Models\LineasTelefonicas;
use App\Models\Planes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeederLineasTelcel extends Seeder
{
    // Telcel en companiaslineastelefonicas
    const TELCEL = 1;

    // Planes del Excel de Telcel 24/10/2026: [nombre en el Excel, nombre anterior en la DB, renta sin IVA].
    // El plan queda con el nombre del Excel y la renta redondeada siempre hacia arriba.
    // Sin nombre anterior = no existía y se crea.
    const PLANES = [
        'BASE'  => ['TELCEL EMPRESA CONTROLADO VPN BASE', 'TELCEL PLUS EMP 1 CONTROLADO', 197.41],
        'VPN1'  => ['TELCEL EMPRESA CONTROLADO VPN 1', 'TELCEL PLUS EMP 1.5 CONTROLADO', 231.90],
        'VPN2'  => ['TELCEL EMPRESA CONTROLADO VPN 2', 'TELCEL PLUS EMP 2 CONTROLADO', 283.62],
        'WIFI3' => ['WiFi TELCEL EMPRESA 3', 'INT EN TU CASA PLUS 3', 343.97],
        'I35'   => ['INTERNET EMPRESA CONTROLADO 3.5', null, 361.21],
        'VPN3'  => ['TELCEL EMPRESA CONTROLADO VPN 3', 'TELCEL PLUS EMP 3 CONTROLADO', 369.83],
        'E4'    => ['TELCEL EMPRESA 4', 'TELCEL PLUS EMP 4 TRADICIONAL', 430.17],
        'C4'    => ['TELCEL EMPRESA CONTROLADO 4', 'TELCEL PLUS EMP 4 CONTROLADO', 473.28],
        'E5'    => ['TELCEL EMPRESA 5', 'TELCEL PLUS EMP 5 TRADICIONAL', 516.38],
        'VPN5'  => ['TELCEL EMPRESA CONTROLADO VPN 5', null, 559.48],
        'E6'    => ['TELCEL EMPRESA 6', null, 602.59],
        'E12'   => ['TELCEL EMPRESA 12', 'TELCEL PLUS EMP 12 TRADICIONAL', 1119.83],
    ];

    public function run()
    {
        DB::transaction(function () {
            $planesCambiados = 0;
            $planes = [];
            foreach (self::PLANES as $clave => [$nombre, $anterior, $sinIva]) {
                $plan = $this->buscarPlan($nombre, $anterior);
                $plan->fill(['NombrePlan' => $nombre, 'PrecioPlan' => ceil($sinIva)]);
                if ($plan->isDirty()) {
                    $plan->save();
                    $planesCambiados++;
                }
                $planes[$clave] = $plan;
            }

            $cambios = [
                // Tenían el precio con IVA
                '9991025131' => 'BASE', '9999958220' => 'BASE', '9993603915' => 'BASE', '9995075857' => 'BASE',
                '9995457586' => 'BASE', '9995451099' => 'BASE', '9994457355' => 'BASE',
                '9995533253' => 'VPN1', '9995533246' => 'VPN1', '9995533256' => 'VPN1', '9995533249' => 'VPN1', '9995533255' => 'VPN1',
                '9994413244' => 'C4',
                // Tenían otro plan
                '9991928498' => 'BASE', '9999010234' => 'BASE', '9995669415' => 'BASE', '9993724248' => 'BASE',
                '9994454343' => 'I35',
                '9995754788' => 'C4',
                '9999650059' => 'VPN5',
                '9999002623' => 'E6',
                // Estaba con plan de AT&T
                '9994156180' => 'BASE',
                // TMSLE 1000 / TMSLE 2000: mismo plan del Excel que BASE / VPN2, se juntan para no duplicar nombres
                '9992335242' => 'BASE', '9994452163' => 'BASE', '9995538745' => 'BASE', '9999478907' => 'BASE',
                '9991219998' => 'VPN2',
            ];

            // No existían; obra = la más usada por su cuenta padre
            $nuevas = [
                '9994450816' => ['CuentaPadre' => '83728880', 'CuentaHija' => '83897169', 'ObraID' => 16],
                '9993350408' => ['CuentaPadre' => '83728853', 'CuentaHija' => '83897165', 'ObraID' => 13],
            ];

            $actualizadas = 0;
            foreach ($cambios as $numero => $clave) {
                $linea = LineasTelefonicas::where('NumTelefonico', $numero)->first();
                if (!$linea) {
                    $this->command->warn("No existe la línea {$numero}");
                    continue;
                }
                $linea->update(['PlanID' => $planes[$clave]->ID]);
                $actualizadas++;
            }

            $creadas = 0;
            foreach ($nuevas as $numero => $datos) {
                if (LineasTelefonicas::where('NumTelefonico', $numero)->exists()) {
                    continue;
                }
                LineasTelefonicas::create($datos + [
                    'NumTelefonico' => $numero,
                    'PlanID' => $planes['BASE']->ID,
                    'TipoLinea' => 'Voz',
                    'CostoFianza' => 0,
                    'Activo' => true,
                    'Disponible' => true,
                ]);
                $creadas++;
            }

            // Nombre y precio nuevos a todas las asignaciones de esos planes
            foreach ($planes as $plan) {
                foreach (LineasTelefonicas::where('PlanID', $plan->ID)->pluck('NumTelefonico') as $numero) {
                    $this->sincronizarInventario($numero, $plan);
                }
            }

            $this->command->info("Líneas Telcel actualizadas: {$actualizadas}, creadas: {$creadas}, planes con nombre/precio nuevo: {$planesCambiados}");
        });
    }

    // Por el nombre del Excel (ya corrido) o por el anterior; si hay repetidos, el que tiene líneas
    private function buscarPlan($nombre, $anterior)
    {
        foreach (array_filter([$nombre, $anterior]) as $buscado) {
            $plan = Planes::where('CompaniaID', self::TELCEL)
                // Algunos nombres traen espacio al final en la DB
                ->whereRaw('TRIM(NombrePlan) = ?', [$buscado])
                ->orderByRaw('(SELECT COUNT(*) FROM lineastelefonicas l WHERE l.PlanID = planes.ID AND l.deleted_at IS NULL) DESC')
                ->first();
            if ($plan) {
                return $plan;
            }
        }
        if ($anterior) {
            throw new \RuntimeException("No existe el plan {$anterior}");
        }
        return new Planes(['CompaniaID' => self::TELCEL]);
    }

    // Mismos campos de plan que LineasTelefonicasController::sincronizarConInventario
    private function sincronizarInventario($numero, $plan)
    {
        foreach (InventarioLineas::where('NumTelefonico', $numero)->get() as $registro) {
            $registro->update([
                'PlanID' => $plan->ID,
                'PlanTel' => $plan->NombrePlan,
                'Compania' => 'Telcel',
                'CostoRentaMensual' => $plan->PrecioPlan,
            ]);
        }
    }
}
