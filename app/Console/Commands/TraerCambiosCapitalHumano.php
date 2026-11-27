<?php

namespace App\Console\Commands;

use App\Services\SincronizacionCapitalHumano;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TraerCambiosCapitalHumano extends Command
{
    protected $signature = 'capital-humano:traer';

    protected $description = 'Trae altas, bajas y cambios de lugar desde Capital Humano';

    public function handle(SincronizacionCapitalHumano $sincronizacion): int
    {
        try {
            $resultado = $sincronizacion->traer();
        } catch (RuntimeException $e) {
            Log::error('No se pudieron traer los cambios de Capital Humano: '.$e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $resumen = 'Plantilla al día. Altas: '.$resultado['altas']
            .'. Bajas sin inventario: '.($resultado['bajas_auto'] ?? 0)
            .'. Bajas con inventario: '.$resultado['bajas']
            .'. Cambios de lugar: '.$resultado['cambios']
            .'. Sin puesto o ubicación: '.($resultado['omitidos'] ?? 0).'.';

        Log::info($resumen);
        $this->info($resumen);

        return self::SUCCESS;
    }
}
