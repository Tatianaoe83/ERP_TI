<?php

namespace App\Exports;

use App\Models\Mantenimiento;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MantenimientosExport implements WithMultipleSheets
{
    public function __construct(protected string $anioFiltro) {}

    public function sheets(): array
    {
        $query = Mantenimiento::select('AnioProgramacion')
            ->whereNotNull('AnioProgramacion')
            ->distinct()
            ->orderBy('AnioProgramacion');

        if ($this->anioFiltro !== 'todos') {
            $query->where('AnioProgramacion', (int) $this->anioFiltro);
        }

        $sheets = [];

        foreach ($query->pluck('AnioProgramacion') as $anio) {
            $sheets[] = new MantenimientoAnioSheet((int) $anio);
            $sheets[] = new MantenimientoResumenSheet((int) $anio);
        }

        return $sheets;
    }
}
