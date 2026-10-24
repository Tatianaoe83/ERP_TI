<?php

namespace Database\Seeders;

use App\Models\InventarioLineas;
use App\Models\LineasTelefonicas;
use App\Models\Planes;
use Illuminate\Database\Seeder;

class SeederLineasTelcel extends Seeder
{
    // Telcel en companiaslineastelefonicas
    const TELCEL = 1;

    public function run()
    {
        $planes = [
            // Existían en la tabla de planes
            'BASE' => $this->planExistente('TELCEL PLUS EMP 1 CONTROLADO'),
            'VPN1' => $this->planExistente('TELCEL PLUS EMP 1.5 CONTROLADO'),
            'C4'   => $this->planExistente('TELCEL PLUS EMP 4 CONTROLADO'),
            // No existían en la tabla de planes, se crean con el precio que tenían en la línea
            'I35'  => Planes::firstOrCreate(['CompaniaID' => self::TELCEL, 'NombrePlan' => 'INTERNET EMPRESA CONTROLADO 3.5'], ['PrecioPlan' => 361.21]),
            'VPN5' => Planes::firstOrCreate(['CompaniaID' => self::TELCEL, 'NombrePlan' => 'TELCEL EMPRESA CONTROLADO VPN 5'], ['PrecioPlan' => 559.48]),
            'E6'   => Planes::firstOrCreate(['CompaniaID' => self::TELCEL, 'NombrePlan' => 'TELCEL EMPRESA 6'], ['PrecioPlan' => 602.59]),
        ];

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
            $this->sincronizarInventario($numero, $planes[$clave]);
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

        $this->command->info("Líneas Telcel actualizadas: {$actualizadas}, creadas: {$creadas}");
    }

    private function planExistente($nombre)
    {
        // Algunos nombres traen espacio al final en la DB
        return Planes::where('CompaniaID', self::TELCEL)->whereRaw('TRIM(NombrePlan) = ?', [$nombre])->firstOrFail();
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
