<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithTitle;

class ResumenSheetExport implements FromView, ShouldAutoSize, WithEvents, WithCharts,WithTitle
{
    protected $tickets;
    protected $resumen;
    protected $tiempoPorEmpleado;
    protected $tiempoPorCategoria;
    protected $mes;
    protected $anio;
    protected $catalogo;

    public int $cantidadUsuarios      = 0;
    public int $cantidadFilasGerencia = 0;
    public int $cantidadCategorias    = 0;
    public int $cantidadMeses         = 0;
    public int $cantidadUsuariosMeses = 0;

    public function __construct($tickets, $resumen, $tiempoPorEmpleado, $tiempoPorCategoria, $mes, $anio, $catalogo = [])
    {
        $this->tickets            = $tickets instanceof Collection ? $tickets : collect($tickets);
        $this->resumen            = is_array($resumen) ? $resumen : [];
        $this->tiempoPorEmpleado  = $tiempoPorEmpleado;
        $this->tiempoPorCategoria = $tiempoPorCategoria;
        $this->mes                = $mes;
        $this->anio               = $anio;
        $this->catalogo           = $catalogo;
    }

    private function formatSecondsToDays($seconds): string
    {
        if (!$seconds || $seconds <= 0) return "0.00:00:00";
        $days    = floor($seconds / 86400);
        $hours   = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs    = $seconds % 60;
        return sprintf("%d.%02d:%02d:%02d", $days, $hours, $minutes, $secs);
    }

    private function col(int $index): string
    {
        return Coordinate::stringFromColumnIndex($index);
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function view(): View
    {
        $mesTarget  = (is_numeric($this->mes)  && $this->mes  >= 1    && $this->mes  <= 12)   ? (int) $this->mes  : now()->month;
        $anioTarget = (is_numeric($this->anio) && $this->anio >= 2000 && $this->anio <= 2100) ? (int) $this->anio : now()->year;

        $fechaTarget       = Carbon::create($anioTarget, $mesTarget, 1);
        $mesNombreTarget   = $fechaTarget->locale('es')->translatedFormat('F Y');
        
        $mesAnterior       = $fechaTarget->copy()->subMonth()->month;
        $anioAnterior      = $fechaTarget->copy()->subMonth()->year;
        $mesNombreAnterior = $fechaTarget->copy()->subMonth()->locale('es')->translatedFormat('F Y');

        $tickets = $this->tickets;

        $usuariosUnicos     = [];
        $mesActualCorto     = $fechaTarget->locale('es')->translatedFormat('F');
        $mesAnteriorCorto   = $fechaTarget->copy()->subMonth()->locale('es')->translatedFormat('F');
        $usuariosAmbosMeses = [];
        $tablaMesesUsuarios = [];
        
        $tablaCategoria     = []; // Solo usaremos esta para el mes actual

        $segundosNormales            = [];
        $segundosTotales             = [];
        $segundosPrimerRespGenerales = [];
        $totalTicketsMesActual       = 0;

        $segundosNormalesAnt            = [];
        $segundosTotalesAnt             = [];
        $segundosPrimerRespGeneralesAnt = [];
        $totalTicketsMesAnterior        = 0;
        $cerradosMesAnterior            = 0;

        // 1. Obtener usuarios únicos y preparar tabla final
        foreach ($tickets as $ticket) {
            $ticketDate = Carbon::parse($ticket->created_at);
            $usuario = (string) (optional($ticket->responsableTI)->NombreEmpleado ?? 'Sin Responsable');

            if ($ticketDate->month === $mesTarget && $ticketDate->year === $anioTarget) {
                $usuariosUnicos[$usuario] = $usuario;
            }

            if (($ticketDate->month === $mesTarget && $ticketDate->year === $anioTarget) || 
                ($ticketDate->month === $mesAnterior && $ticketDate->year === $anioAnterior)) {
                $usuariosAmbosMeses[$usuario] = $usuario;
            }
        }
        ksort($usuariosUnicos);
        ksort($usuariosAmbosMeses);

        foreach ($usuariosAmbosMeses as $usr) {
            $tablaMesesUsuarios[$usr] = [
                $mesAnteriorCorto => 0,
                $mesActualCorto   => 0
            ];
        }

        // 2. Construir catálogo de 2 niveles: Gerencia -> Tertipo
        $this->catalogo = [];
        foreach ($tickets as $ticket) {
            $gerencia = (string) optional($ticket->tipoticket)->NombreTipo;
            $tertipo  = (string) optional($ticket->tertipo)->NombreTertipo;

            if (empty($gerencia)) continue;

            if (!isset($this->catalogo[$gerencia])) {
                $this->catalogo[$gerencia] = [];
            }
            if (!empty($tertipo) && !in_array($tertipo, $this->catalogo[$gerencia])) {
                $this->catalogo[$gerencia][] = $tertipo;
            }
        }

        ksort($this->catalogo);
        foreach ($this->catalogo as $gerencia => $ters) {
            sort($this->catalogo[$gerencia]);
        }

        // 3. Inicializar tabla agrupada en 0 (2 niveles)
        $tablaAgrupada = [];
        foreach ($this->catalogo as $gerencia => $tertipos) {
            $tablaAgrupada[$gerencia]['total_principal'] = array_fill_keys(array_keys($usuariosUnicos), 0);
            foreach ($tertipos as $ter) {
                $tablaAgrupada[$gerencia]['tertipos'][$ter] = array_fill_keys(array_keys($usuariosUnicos), 0);
            }
        }

        // 4. Llenar datos
        foreach ($tickets as $ticket) {
            $ticketDate   = Carbon::parse($ticket->created_at);
            $ticketMes    = $ticketDate->month;
            $ticketAnio   = $ticketDate->year;
            $usuario      = (string) (optional($ticket->responsableTI)->NombreEmpleado ?? 'Sin Responsable');
            
            $gerencia  = (string) optional($ticket->tipoticket)->NombreTipo;
            $tertipo   = (string) optional($ticket->tertipo)->NombreTertipo;
            $categoria = !empty($tertipo) ? $tertipo : 'Sin Categoría'; 

            // SI ES EL MES ACTUAL
            if ($ticketMes === $mesTarget && $ticketAnio === $anioTarget) {
                $totalTicketsMesActual++;
                $tablaMesesUsuarios[$usuario][$mesActualCorto]++;

                if (!empty($gerencia) && isset($tablaAgrupada[$gerencia])) {
                    $tablaAgrupada[$gerencia]['total_principal'][$usuario]++;
                    if (!empty($tertipo) && isset($tablaAgrupada[$gerencia]['tertipos'][$tertipo])) {
                        $tablaAgrupada[$gerencia]['tertipos'][$tertipo][$usuario]++;
                    }
                }

                // Cargar datos SOLO para el mes actual en $tablaCategoria
                if (!isset($tablaCategoria[$categoria])) {
                    $tablaCategoria[$categoria] = ['total' => 0, 'segundos_resolucion' => [], 'segundos_primer_respuesta' => []];
                }
                $tablaCategoria[$categoria]['total']++;

                if (!empty($ticket->FechaInicioProgreso)) {
                    try {
                        $diffPrimer = $ticketDate->diffInSeconds(Carbon::parse($ticket->FechaInicioProgreso));
                        $tablaCategoria[$categoria]['segundos_primer_respuesta'][] = $diffPrimer;
                        $segundosPrimerRespGenerales[] = $diffPrimer;
                    } catch (\Exception $e) {}
                }

                if (!empty($ticket->FechaFinProgreso) && $ticket->Estatus === 'Cerrado') {
                    try {
                        $diffRes = $ticketDate->diffInSeconds(Carbon::parse($ticket->FechaFinProgreso));
                        $tablaCategoria[$categoria]['segundos_resolucion'][] = $diffRes;
                        $segundosTotales[] = $diffRes;
                        if ($diffRes <= 28800) $segundosNormales[] = $diffRes;
                    } catch (\Exception $e) {}
                }

            // SI ES EL MES ANTERIOR
            } elseif ($ticketMes === $mesAnterior && $ticketAnio === $anioAnterior) {
                $totalTicketsMesAnterior++;
                $tablaMesesUsuarios[$usuario][$mesAnteriorCorto]++;
                if ($ticket->Estatus === 'Cerrado') $cerradosMesAnterior++;

                if (!empty($ticket->FechaInicioProgreso)) {
                    try {
                        $diffPrimer = $ticketDate->diffInSeconds(Carbon::parse($ticket->FechaInicioProgreso));
                        $segundosPrimerRespGeneralesAnt[] = $diffPrimer;
                    } catch (\Exception $e) {}
                }

                if (!empty($ticket->FechaFinProgreso) && $ticket->Estatus === 'Cerrado') {
                    try {
                        $diffRes = $ticketDate->diffInSeconds(Carbon::parse($ticket->FechaFinProgreso));
                        $segundosTotalesAnt[] = $diffRes;
                        if ($diffRes <= 28800) $segundosNormalesAnt[] = $diffRes;
                    } catch (\Exception $e) {}
                }
            }
        }

        ksort($tablaAgrupada);
        ksort($tablaCategoria);

        $filasJerarquia = 0;
        foreach ($tablaAgrupada as $gerencia => $datos) {
            $filasJerarquia++; // Padre
            if (isset($datos['tertipos'])) {
                $filasJerarquia += count($datos['tertipos']); // Hijo
            }
        }

        $this->cantidadUsuarios      = count($usuariosUnicos);
        $this->cantidadFilasGerencia = $filasJerarquia;
        $this->cantidadCategorias    = count($tablaCategoria);
        $this->cantidadMeses         = count($usuariosAmbosMeses);
        $this->cantidadUsuariosMeses = count($tablaMesesUsuarios);

        // Calcular promedios para mes Actual
        foreach ($tablaCategoria as $key => $data) {
            $promRes    = count($data['segundos_resolucion']) > 0 ? array_sum($data['segundos_resolucion']) / count($data['segundos_resolucion']) : 0;
            $tablaCategoria[$key]['promedio_resolucion'] = $this->formatSecondsToDays($promRes);
        }

        $promedioNormales               = count($segundosNormales)            > 0 ? array_sum($segundosNormales)            / count($segundosNormales)            : 0;
        $promedioTotales                = count($segundosTotales)             > 0 ? array_sum($segundosTotales)             / count($segundosTotales)             : 0;
        $promedioPrimerRespuestaGeneral = count($segundosPrimerRespGenerales) > 0 ? array_sum($segundosPrimerRespGenerales) / count($segundosPrimerRespGenerales) : 0;
        $cumplimiento                   = $this->resumen['porcentaje_cumplimiento'] ?? 0;

        $promedioNormalesAnt               = count($segundosNormalesAnt)            > 0 ? array_sum($segundosNormalesAnt)            / count($segundosNormalesAnt)            : 0;
        $promedioTotalesAnt                = count($segundosTotalesAnt)             > 0 ? array_sum($segundosTotalesAnt)             / count($segundosTotalesAnt)             : 0;
        $promedioPrimerRespuestaGeneralAnt = count($segundosPrimerRespGeneralesAnt) > 0 ? array_sum($segundosPrimerRespGeneralesAnt) / count($segundosPrimerRespGeneralesAnt) : 0;
        $cumplimientoAnt                   = $totalTicketsMesAnterior > 0 ? round(($cerradosMesAnterior / $totalTicketsMesAnterior) * 100, 0) : 0;

        return view('tickets.export.resumen-excel', [
            'tablaAgrupada'           => $tablaAgrupada,
            'usuariosUnicos'          => $usuariosUnicos,
            'tablaCategoria'          => $tablaCategoria,
            'totalTickets'            => $totalTicketsMesActual,
            'usuariosAmbosMeses'      => $usuariosAmbosMeses,
            'tablaMesesUsuarios'      => $tablaMesesUsuarios,
            'mesActualCorto'          => $mesActualCorto,
            'mesAnteriorCorto'        => $mesAnteriorCorto,
            'mesNombreTarget'         => $mesNombreTarget,
            'mesNombreAnterior'       => $mesNombreAnterior,
            'promResolucionNormal'    => $this->formatSecondsToDays($promedioNormales),
            'promResolucionTotal'     => $this->formatSecondsToDays($promedioTotales),
            'promPrimerRespuesta'     => $this->formatSecondsToDays($promedioPrimerRespuestaGeneral),
            'cumplimiento'            => number_format((float) $cumplimiento, 0) . '%',
            'promResolucionNormalAnt' => $this->formatSecondsToDays($promedioNormalesAnt),
            'promResolucionTotalAnt'  => $this->formatSecondsToDays($promedioTotalesAnt),
            'promPrimerRespuestaAnt'  => $this->formatSecondsToDays($promedioPrimerRespuestaGeneralAnt),
            'cumplimientoAnt'         => $cumplimientoAnt . '%',
            'textoAnormales'          => "Generalmente los tickets de duración anormal son aquellos que exceden el día laboral de duración ( >8 hrs) y tiene que ver con falta de respuesta del que crea el ticket, incorrecta ejecución del proceso de atención (TI; principalmente en los primeros meses de la implementación del sistema), problema de multiples respuestas, o escalado.",
            'ticketsCerrados'         => $this->resumen['tickets_cerrados'] ?? 0,
            'promResolucionHoras'     => number_format($promedioTotales / 3600, 1),
            'promRespuestaHoras'      => number_format($promedioPrimerRespuestaGeneral / 3600, 1),
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Anchos de columna y ajuste de texto para la tabla de resumen KPI (filas 1-4, columnas A-E)
                foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(20);
                }
                $sheet->getStyle('A3:E4')->getAlignment()->setWrapText(true);

                if ($this->cantidadFilasGerencia === 0) {
                    return;
                }

                $uCount = $this->cantidadUsuarios;

                $sheet->setShowSummaryBelow(false);

                // Fila 1 = Título, Fila 2 = Encabezados, Fila 3 = Inicio de Datos
                // Asumimos que la Fila 1 es Título, Fila 2 son Encabezados, Fila 3 inicia Datos
                $currentRow = 2; 

                foreach ($this->catalogo as $gerencia => $tertipos) {
                    $filaGerencia = ++$currentRow; // Fila Nivel 0 (Padre)

                    if (is_array($tertipos) && count($tertipos) > 0) {
                        foreach ($tertipos as $ter) {
                            $filaTer = ++$currentRow; // Fila Nivel 1 (Hijo)
                            
                            // Agrupar y ocultar Tertipos
                            $dimTer = $sheet->getRowDimension($filaTer);
                            $dimTer->setOutlineLevel(1);
                            $dimTer->setVisible(false);
                        }
                        // Marcar la Gerencia para que tenga el botón [+]
                        $sheet->getRowDimension($filaGerencia)->setCollapsed(true);
                    }
                }

                $colGerEnd = $uCount + 2; 
                $rangeGer = "A2:" . $this->col($colGerEnd) . $currentRow;

                try {
                    $tableGer = new Table($rangeGer);
                    $tableGer->setName('TablaTiposTicket');
                    $tableGer->setShowTotalsRow(false);
                    $sheet->addTable($tableGer);
                } catch (\Exception $e) {
                    $sheet->setAutoFilter($rangeGer);
                }

                $sheet->freezePane('A3');
            },
        ];
    }

    public function charts()
    {
        if ($this->cantidadFilasGerencia === 0 || $this->cantidadUsuarios === 0) {
            return [];
        }

        $filaInicio = 3;
        $filaFin    = $filaInicio + $this->cantidadFilasGerencia - 1;

        $ejeY = [
            new DataSeriesValues('String', "Resumen!\$A\${$filaInicio}:\$A\${$filaFin}", null, $this->cantidadFilasGerencia),
        ];

        $seriesNombres = [];
        $valores       = [];

        for ($u = 0; $u < $this->cantidadUsuarios; $u++) {
            $colLetra        = $this->col($u + 3); 
            $seriesNombres[] = new DataSeriesValues('String', "Resumen!\${$colLetra}\$2", null, 1);
            $valores[]       = new DataSeriesValues('Number', "Resumen!\${$colLetra}\${$filaInicio}:\${$colLetra}\${$filaFin}", null, $this->cantidadFilasGerencia);
        }

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_STACKED,
            range(0, count($valores) - 1),
            $seriesNombres,
            $ejeY,
            $valores
        );
        $series->setPlotDirection(DataSeries::DIRECTION_BAR);

        $plotArea = new PlotArea(null, [$series]);
        $legend   = new Legend(Legend::POSITION_BOTTOM, null, false);
        $title    = new Title('Incidencias por Tipo/Tertipo y Responsable');

        $chart = new Chart('grafica_gerencias', $title, $legend, $plotArea);

        $totalFilasOcupadasHaciaAbajo = $this->cantidadFilasGerencia + $this->cantidadCategorias + $this->cantidadUsuariosMeses + 15;
        $filaDondeEmpiezaGrafica = $totalFilasOcupadasHaciaAbajo;
        $filaDondeTerminaGrafica = $filaDondeEmpiezaGrafica + 20;

        $chart->setTopLeftPosition("A{$filaDondeEmpiezaGrafica}");
        $chart->setBottomRightPosition("L{$filaDondeTerminaGrafica}");

        return [$chart];
    }
}