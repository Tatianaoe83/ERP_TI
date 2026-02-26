<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReporteMensualTicketsExport implements WithMultipleSheets
{
    protected $tickets;
    protected $resumen;
    protected $tiempoPorEmpleado;
    protected $tiempoPorCategoria;
    protected $mes;
    protected $anio;

    public function __construct($tickets, $resumen, $tiempoPorEmpleado, $tiempoPorCategoria, $mes, $anio)
    {
        $this->tickets = $tickets;
        $this->resumen = $resumen;
        $this->tiempoPorEmpleado = $tiempoPorEmpleado;
        $this->tiempoPorCategoria = $tiempoPorCategoria;
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function sheets(): array
    {
        return [
            new ResumenSheetExport(
                $this->tickets, 
                $this->resumen, 
                $this->tiempoPorEmpleado, 
                $this->tiempoPorCategoria, 
                $this->mes, 
                $this->anio
            ),
            new TicketsSheetExport($this->tickets, $this->resumen, $this->mes, $this->anio),
            new TiempoResolucionPorEmpleadoSheetExport($this->tiempoPorEmpleado, $this->mes, $this->anio),
            new TiempoPorCategoriaResponsableSheetExport($this->tiempoPorCategoria, $this->mes, $this->anio),
        ];
    }
}