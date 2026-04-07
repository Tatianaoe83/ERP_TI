<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReporteMensualTicketsExport implements WithMultipleSheets
{
    protected $tickets;
    protected $ticketsMesActual;
    protected $resumen;
    protected $tiempoPorEmpleado;
    protected $tiempoPorCategoria;
    protected $mes;
    protected $anio;
    protected $catalogo;
    protected $solicitudes;
    protected $metricasSolicitudes;

    public function __construct($tickets, $resumen, $tiempoPorEmpleado, $tiempoPorCategoria, $mes, $anio, $ticketsMesActual = null, $catalogo = [], $solicitudes = [], $metricasSolicitudes = [])
    {
        $this->tickets = $tickets;
        $this->ticketsMesActual = $ticketsMesActual ?? $tickets;
        $this->resumen = $resumen;
        $this->tiempoPorEmpleado = $tiempoPorEmpleado;
        $this->tiempoPorCategoria = $tiempoPorCategoria;
        $this->mes = $mes;
        $this->anio = $anio;
        $this->catalogo = $catalogo;
        $this->solicitudes = $solicitudes;
        $this->metricasSolicitudes = $metricasSolicitudes;
    }

    public function sheets(): array
    {
        return [
            new ResumeGraphicsSheetExport(
                $this->tickets,
                $this->resumen,
                $this->tiempoPorEmpleado,
                $this->tiempoPorCategoria,
                $this->mes,
                $this->anio,
                $this->catalogo,
                $this->solicitudes,
                $this->metricasSolicitudes
            ),
            new ResumenSheetExport(
                $this->tickets,
                $this->resumen,
                $this->tiempoPorEmpleado,
                $this->tiempoPorCategoria,
                $this->mes,
                $this->anio,
                $this->catalogo,
                $this->solicitudes,
                $this->metricasSolicitudes
            ),
            new TicketsSheetExport($this->ticketsMesActual, $this->resumen, $this->mes, $this->anio),
            new SolicitudesSheetExport($this->solicitudes, $this->metricasSolicitudes, $this->mes, $this->anio),
            new TiempoResolucionPorEmpleadoSheetExport($this->tiempoPorEmpleado, $this->mes, $this->anio),
            new TiempoPorCategoriaResponsableSheetExport($this->tiempoPorCategoria, $this->mes, $this->anio),
        ];
    }
}