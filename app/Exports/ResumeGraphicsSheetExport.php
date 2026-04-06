<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithTitle;

class ResumeGraphicsSheetExport implements FromArray, WithCharts, WithTitle
{
    protected $tickets;
    protected $resumen;
    protected $tiempoPorEmpleado;
    protected $tiempoPorCategoria;
    protected $mes;
    protected $anio;
    protected $catalogo;
    protected $resumenSheet;

    public function __construct($tickets, $resumen, $tiempoPorEmpleado, $tiempoPorCategoria, $mes, $anio, $catalogo = [])
    {
        $this->tickets = $tickets instanceof Collection ? $tickets : collect($tickets);
        $this->resumen = is_array($resumen) ? $resumen : [];
        $this->tiempoPorEmpleado = $tiempoPorEmpleado;
        $this->tiempoPorCategoria = $tiempoPorCategoria;
        $this->mes = $mes;
        $this->anio = $anio;
        $this->catalogo = $catalogo;

        // Crear instancia de ResumenSheetExport para acceder a datos y gráficas
        $this->resumenSheet = new ResumenSheetExport(
            $this->tickets,
            $this->resumen,
            $this->tiempoPorEmpleado,
            $this->tiempoPorCategoria,
            $this->mes,
            $this->anio,
            $this->catalogo
        );
    }

    public function title(): string
    {
        return 'Gráficas';
    }

    public function array(): array
    {
        return [[]]; // Array vacío, solo para que exista la hoja
    }

    public function charts(): array
    {
        // Las gráficas se refieren a la hoja Resumen, no a esta hoja (Gráficas)
        return $this->resumenSheet->getChartsForSheet('Resumen');
    }
}

