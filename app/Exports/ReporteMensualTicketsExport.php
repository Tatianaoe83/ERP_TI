<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReporteMensualTicketsExport implements WithMultipleSheets
{
    protected $tickets;
    protected $resumen;
    protected $mes;
    protected $anio;

    public function __construct($tickets, $resumen, $mes, $anio)
    {
        $this->tickets = $tickets;
        $this->resumen = $resumen;
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function sheets(): array
    {
        return [
            new ResumenSheetExport($this->resumen, $this->mes, $this->anio),
            new TicketsSheetExport($this->tickets, $this->resumen, $this->mes, $this->anio),
        ];
    }
}
