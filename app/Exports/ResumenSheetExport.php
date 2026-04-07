<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResumenSheetExport implements FromArray, WithEvents, WithTitle
{
    protected $tickets;
    protected $resumen;
    protected $tiempoPorEmpleado;
    protected $tiempoPorCategoria;
    protected $mes;
    protected $anio;
    protected $catalogo;
    protected $solicitudes;
    protected $metricasSolicitudes;
    protected $tertipoAPadres = [];

    protected array $reportData = [];
    protected array $layout = [];
    protected bool $prepared = false;
    protected int $maxColumns = 5;

    protected array $coloresResponsables = [
        '2563EB',
        'EA580C',
        '059669',
        '7C3AED',
        'DC2626',
        '0891B2',
    ];

    public function __construct($tickets, $resumen, $tiempoPorEmpleado, $tiempoPorCategoria, $mes, $anio, $catalogo = [], $solicitudes = [], $metricasSolicitudes = [])
    {
        $this->tickets = $tickets instanceof Collection ? $tickets : collect($tickets);
        $this->resumen = is_array($resumen) ? $resumen : [];
        $this->tiempoPorEmpleado = $tiempoPorEmpleado;
        $this->tiempoPorCategoria = $tiempoPorCategoria;
        $this->mes = $mes;
        $this->anio = $anio;
        $this->catalogo = $catalogo;
        $this->solicitudes = $solicitudes;
        $this->metricasSolicitudes = $metricasSolicitudes;
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function array(): array
    {
        \Log::info('ResumenSheetExport array() - tickets count: ' . count($this->tickets));
        $this->prepareReportData();

        \Log::info('ResumenSheetExport after prepareReportData - reportData keys: ' . implode(', ', array_keys($this->reportData)));
        \Log::info('ResumenSheetExport - usuariosUnicos count: ' . ($this->reportData['usuarios'] ? count($this->reportData['usuarios']) : 0));

        $rows = $this->buildRows();

        \Log::info('ResumenSheetExport - rows count: ' . count($rows));

        return $this->normalizeRows($rows);
    }

    private function prepareReportData(): void
    {
        if ($this->prepared) {
            \Log::warning('ResumenSheetExport prepareReportData already prepared, returning early');
            return;
        }

        $mesTarget = (is_numeric($this->mes) && $this->mes >= 1 && $this->mes <= 12) ? (int) $this->mes : now()->month;
        $anioTarget = (is_numeric($this->anio) && $this->anio >= 2000 && $this->anio <= 2100) ? (int) $this->anio : now()->year;

        $fechaTarget = Carbon::create($anioTarget, $mesTarget, 1);
        $mesNombreTarget = $fechaTarget->locale('es')->translatedFormat('F Y');

        $mesAnterior = $fechaTarget->copy()->subMonth()->month;
        $anioAnterior = $fechaTarget->copy()->subMonth()->year;
        $mesNombreAnterior = $fechaTarget->copy()->subMonth()->locale('es')->translatedFormat('F Y');

        $tickets = $this->tickets;

        \Log::info('ResumenSheetExport prepareReportData start - tickets count: ' . count($tickets));

        $usuariosUnicos = [];
        $mesActualCorto = $fechaTarget->locale('es')->translatedFormat('F');
        $mesAnteriorCorto = $fechaTarget->copy()->subMonth()->locale('es')->translatedFormat('F');
        $usuariosAmbosMeses = [];
        $tablaMesesUsuarios = [];
        $tablaCategoria = [];

        $segundosNormales = [];
        $segundosTotales = [];
        $segundosPrimerRespGenerales = [];
        $totalTicketsMesActual = 0;
        $ticketsCerradosActualCalculado = 0;

        $segundosNormalesAnt = [];
        $segundosTotalesAnt = [];
        $segundosPrimerRespGeneralesAnt = [];
        $totalTicketsMesAnterior = 0;
        $cerradosMesAnterior = 0;

        foreach ($tickets as $ticket) {
            $ticketDate = Carbon::parse($ticket->created_at);
            $usuario = (string) (optional($ticket->responsableTI)->NombreEmpleado ?? 'Sin Responsable');

            if ($ticketDate->month === $mesTarget && $ticketDate->year === $anioTarget) {
                $usuariosUnicos[$usuario] = $usuario;
            }

            if (
                ($ticketDate->month === $mesTarget && $ticketDate->year === $anioTarget)
                || ($ticketDate->month === $mesAnterior && $ticketDate->year === $anioAnterior)
            ) {
                $usuariosAmbosMeses[$usuario] = $usuario;
            }
        }

        ksort($usuariosUnicos);
        ksort($usuariosAmbosMeses);

        foreach ($usuariosAmbosMeses as $usr) {
            $tablaMesesUsuarios[$usr] = [
                $mesAnteriorCorto => 0,
                $mesActualCorto => 0,
            ];
        }

        $this->catalogo = [];
        $this->tertipoAPadres = [];
        $ticketsSinClasificar = 0;

        try {
            $filasCatalogo = DB::select("
                SELECT DISTINCT tt.NombreTipo, st.NombreSubtipo, ter.NombreTertipo
                FROM tipotickets tt
                INNER JOIN subtipo st ON tt.SubtipoID = st.SubtipoID
                INNER JOIN tertipo ter ON st.TertipoID = ter.TertipoID
                WHERE tt.deleted_at IS NULL
                  AND st.deleted_at IS NULL
                  AND ter.deleted_at IS NULL
                ORDER BY tt.NombreTipo, st.NombreSubtipo, ter.NombreTertipo
            ");

            foreach ($filasCatalogo as $fila) {
                $tipo = (string) ($fila->NombreTipo ?? '');
                $subtipo = (string) ($fila->NombreSubtipo ?? '');
                $tertipo = (string) ($fila->NombreTertipo ?? '');

                if ($tipo === '' || $subtipo === '' || $tertipo === '') {
                    continue;
                }

                if (!isset($this->catalogo[$tipo])) {
                    $this->catalogo[$tipo] = [];
                }
                if (!isset($this->catalogo[$tipo][$subtipo])) {
                    $this->catalogo[$tipo][$subtipo] = [];
                }
                if (!in_array($tertipo, $this->catalogo[$tipo][$subtipo], true)) {
                    $this->catalogo[$tipo][$subtipo][] = $tertipo;
                }

                $this->tertipoAPadres[$tertipo] = [$tipo, $subtipo];
            }
        } catch (\Throwable $e) {
            $this->tertipoAPadres = [];
        }

        $fechaInicioReporte = Carbon::create($anioTarget, $mesTarget, 1)->subMonth()->startOfMonth();
        $fechaFinReporte = Carbon::create($anioTarget, $mesTarget, 1)->endOfMonth();

        if (empty($this->catalogo)) {
            try {
                $filas = DB::select(
                    "
                    SELECT DISTINCT
                        COALESCE(tt.NombreTipo, 'Sin tipo') AS NombreTipo,
                        COALESCE(st.NombreSubtipo, 'Sin subtipo') AS NombreSubtipo,
                        COALESCE(ter.NombreTertipo, 'Sin incidencia') AS NombreTertipo
                    FROM tickets t
                    LEFT JOIN tipotickets tt ON t.TipoID = tt.TipoID
                    LEFT JOIN subtipo st ON t.SubtipoID = st.SubtipoID
                    LEFT JOIN tertipo ter ON t.TertipoID = ter.TertipoID
                    WHERE t.deleted_at IS NULL
                      AND t.created_at >= ?
                      AND t.created_at <= ?
                    ORDER BY NombreTipo, NombreSubtipo, NombreTertipo
                ",
                    [$fechaInicioReporte, $fechaFinReporte]
                );

                foreach ($filas as $f) {
                    $t = (string) ($f->NombreTipo ?? 'Sin tipo');
                    $s = (string) ($f->NombreSubtipo ?? 'Sin subtipo');
                    $r = (string) ($f->NombreTertipo ?? 'Sin incidencia');

                    if (!isset($this->catalogo[$t])) {
                        $this->catalogo[$t] = [];
                    }
                    if (!isset($this->catalogo[$t][$s])) {
                        $this->catalogo[$t][$s] = [];
                    }
                    if (!in_array($r, $this->catalogo[$t][$s], true)) {
                        $this->catalogo[$t][$s][] = $r;
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        foreach ($tickets as $ticket) {
            $tipo = (string) (optional($ticket->tipoticket)->NombreTipo ?: '');
            $subtipo = (string) (optional($ticket->subtipo)->NombreSubtipo ?: 'Sin subtipo');
            $tertipo = (string) (optional($ticket->tertipo)->NombreTertipo ?: 'Sin incidencia');

            if (empty($tipo)) {
                $tipo = 'Sin tipo';
                $ticketDate = Carbon::parse($ticket->created_at);
                if ($ticketDate->month === $mesTarget && $ticketDate->year === $anioTarget) {
                    $ticketsSinClasificar++;
                }
            }

            if (!isset($this->catalogo[$tipo])) {
                $this->catalogo[$tipo] = [];
            }
            if (!isset($this->catalogo[$tipo][$subtipo])) {
                $this->catalogo[$tipo][$subtipo] = [];
            }
            if (!in_array($tertipo, $this->catalogo[$tipo][$subtipo], true)) {
                $this->catalogo[$tipo][$subtipo][] = $tertipo;
            }
        }

        $sinValores = ['Sin tipo', 'Sin subtipo', 'Sin incidencia'];

        uksort(
            $this->catalogo,
            fn($a, $b) => in_array($a, $sinValores, true) && !in_array($b, $sinValores, true)
                ? 1
                : (!in_array($a, $sinValores, true) && in_array($b, $sinValores, true) ? -1 : strcasecmp($a, $b))
        );

        foreach ($this->catalogo as $tipo => $subtipos) {
            uksort(
                $subtipos,
                fn($a, $b) => ($a === 'Sin subtipo' && $b !== 'Sin subtipo')
                    ? 1
                    : (($a !== 'Sin subtipo' && $b === 'Sin subtipo') ? -1 : strcasecmp($a, $b))
            );
            $this->catalogo[$tipo] = $subtipos;

            foreach ($subtipos as $subtipo => $tertipos) {
                usort(
                    $tertipos,
                    fn($a, $b) => ($a === 'Sin incidencia' && $b !== 'Sin incidencia')
                        ? 1
                        : (($a !== 'Sin incidencia' && $b === 'Sin incidencia') ? -1 : strcasecmp($a, $b))
                );
                $this->catalogo[$tipo][$subtipo] = $tertipos;
            }
        }

        $tablaAgrupada = [];
        foreach ($this->catalogo as $tipo => $subtipos) {
            $tablaAgrupada[$tipo]['total_principal'] = array_fill_keys(array_keys($usuariosUnicos), 0);

            foreach ($subtipos as $subtipo => $tertipos) {
                $tablaAgrupada[$tipo]['subtipos'][$subtipo]['total_principal'] = array_fill_keys(array_keys($usuariosUnicos), 0);

                foreach ($tertipos as $ter) {
                    $tablaAgrupada[$tipo]['subtipos'][$subtipo]['tertipos'][$ter] = array_fill_keys(array_keys($usuariosUnicos), 0);
                }
            }
        }

        foreach ($tickets as $ticket) {
            $ticketDate = Carbon::parse($ticket->created_at);
            $ticketMes = $ticketDate->month;
            $ticketAnio = $ticketDate->year;
            $usuario = (string) (optional($ticket->responsableTI)->NombreEmpleado ?? 'Sin Responsable');

            $tipo = (string) (optional($ticket->tipoticket)->NombreTipo ?: 'Sin tipo');
            $subtipo = (string) (optional($ticket->subtipo)->NombreSubtipo ?: 'Sin subtipo');
            $tertipo = (string) (optional($ticket->tertipo)->NombreTertipo ?: 'Sin incidencia');

            if ($subtipo === 'Sin subtipo' && $tertipo !== 'Sin incidencia' && isset($this->tertipoAPadres[$tertipo])) {
                [$tipo, $subtipo] = $this->tertipoAPadres[$tertipo];
            }

            $categoria = !empty($tertipo) && $tertipo !== 'Sin incidencia'
                ? $tertipo
                : ($subtipo !== 'Sin subtipo' ? $subtipo : $tipo);

            if ($ticketMes === $mesTarget && $ticketAnio === $anioTarget) {
                $totalTicketsMesActual++;
                $tablaMesesUsuarios[$usuario][$mesActualCorto]++;

                if ($ticket->Estatus === 'Cerrado') {
                    $ticketsCerradosActualCalculado++;
                }

                if (!empty($tipo) && isset($tablaAgrupada[$tipo])) {
                    $tablaAgrupada[$tipo]['total_principal'][$usuario]++;

                    if (isset($tablaAgrupada[$tipo]['subtipos'][$subtipo])) {
                        $tablaAgrupada[$tipo]['subtipos'][$subtipo]['total_principal'][$usuario]++;

                        if (isset($tablaAgrupada[$tipo]['subtipos'][$subtipo]['tertipos'][$tertipo])) {
                            $tablaAgrupada[$tipo]['subtipos'][$subtipo]['tertipos'][$tertipo][$usuario]++;
                        }
                    }
                }

                if (!isset($tablaCategoria[$categoria])) {
                    $tablaCategoria[$categoria] = [
                        'total' => 0,
                        'segundos_resolucion' => [],
                        'segundos_primer_respuesta' => [],
                    ];
                }

                $tablaCategoria[$categoria]['total']++;

                if (!empty($ticket->FechaInicioProgreso)) {
                    try {
                        $diffPrimer = $ticketDate->diffInSeconds(Carbon::parse($ticket->FechaInicioProgreso));
                        $tablaCategoria[$categoria]['segundos_primer_respuesta'][] = $diffPrimer;
                        $segundosPrimerRespGenerales[] = $diffPrimer;
                    } catch (\Exception $e) {
                    }
                }

                if (!empty($ticket->FechaFinProgreso) && $ticket->Estatus === 'Cerrado') {
                    try {
                        $diffRes = $ticketDate->diffInSeconds(Carbon::parse($ticket->FechaFinProgreso));
                        $tablaCategoria[$categoria]['segundos_resolucion'][] = $diffRes;
                        $segundosTotales[] = $diffRes;

                        if ($diffRes <= 28800) {
                            $segundosNormales[] = $diffRes;
                        }
                    } catch (\Exception $e) {
                    }
                }
            } elseif ($ticketMes === $mesAnterior && $ticketAnio === $anioAnterior) {
                $totalTicketsMesAnterior++;
                $tablaMesesUsuarios[$usuario][$mesAnteriorCorto]++;

                if ($ticket->Estatus === 'Cerrado') {
                    $cerradosMesAnterior++;
                }

                if (!empty($ticket->FechaInicioProgreso)) {
                    try {
                        $diffPrimer = $ticketDate->diffInSeconds(Carbon::parse($ticket->FechaInicioProgreso));
                        $segundosPrimerRespGeneralesAnt[] = $diffPrimer;
                    } catch (\Exception $e) {
                    }
                }

                if (!empty($ticket->FechaFinProgreso) && $ticket->Estatus === 'Cerrado') {
                    try {
                        $diffRes = $ticketDate->diffInSeconds(Carbon::parse($ticket->FechaFinProgreso));
                        $segundosTotalesAnt[] = $diffRes;

                        if ($diffRes <= 28800) {
                            $segundosNormalesAnt[] = $diffRes;
                        }
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        ksort($tablaAgrupada);
        ksort($tablaCategoria);

        foreach ($tablaAgrupada as $tipo => $datos) {
            $totalTipo = array_sum($datos['total_principal'] ?? []);

            if ($totalTipo === 0) {
                unset($tablaAgrupada[$tipo]);
                continue;
            }

            if (isset($datos['subtipos'])) {
                foreach ($datos['subtipos'] as $subtipo => $datosSub) {
                    $totalSub = array_sum($datosSub['total_principal'] ?? []);
                    if ($totalSub === 0) {
                        unset($tablaAgrupada[$tipo]['subtipos'][$subtipo]);
                    }
                }
            }
        }

        $tablaResponsableDetalle = [];
        foreach ($tickets as $ticket) {
            $ticketDate = Carbon::parse($ticket->created_at);

            if ($ticketDate->month !== $mesTarget || $ticketDate->year !== $anioTarget) {
                continue;
            }

            $resp = (string) (optional($ticket->responsableTI)->NombreEmpleado ?? 'Sin Responsable');
            $tipo = (string) (optional($ticket->tipoticket)->NombreTipo ?: 'Sin tipo');
            $sub = (string) (optional($ticket->subtipo)->NombreSubtipo ?: 'Sin subtipo');
            $ter = (string) (optional($ticket->tertipo)->NombreTertipo ?: 'Sin incidencia');
            $clave = "{$resp}|{$tipo}|{$sub}|{$ter}";

            if (!isset($tablaResponsableDetalle[$clave])) {
                $tablaResponsableDetalle[$clave] = [
                    'responsable' => $resp,
                    'tipo' => $tipo,
                    'subtipo' => $sub,
                    'tertipo' => $ter,
                    'total' => 0,
                    'segundos' => [],
                ];
            }

            $tablaResponsableDetalle[$clave]['total']++;

            if (!empty($ticket->FechaFinProgreso) && $ticket->Estatus === 'Cerrado') {
                try {
                    $tablaResponsableDetalle[$clave]['segundos'][] = $ticketDate->diffInSeconds(Carbon::parse($ticket->FechaFinProgreso));
                } catch (\Exception $e) {
                }
            }
        }

        foreach ($tablaResponsableDetalle as &$r) {
            $r['tiempo_prom'] = count($r['segundos']) > 0
                ? $this->formatSecondsToDays(array_sum($r['segundos']) / count($r['segundos']))
                : '—';
            $r['tiempo_prom_horas'] = count($r['segundos']) > 0
                ? $this->formatSecondsToHours(array_sum($r['segundos']) / count($r['segundos']))
                : 0;
        }
        unset($r);

        uasort(
            $tablaResponsableDetalle,
            fn($a, $b) => strcmp($a['responsable'], $b['responsable'])
                ?: (strcmp($a['tipo'], $b['tipo'])
                    ?: (strcmp($a['subtipo'], $b['subtipo']) ?: strcmp($a['tertipo'], $b['tertipo'])))
        );

        $tablaCategoriaDetallada = [];
        foreach ($tickets as $ticket) {
            $ticketDate = Carbon::parse($ticket->created_at);

            if ($ticketDate->month !== $mesTarget || $ticketDate->year !== $anioTarget) {
                continue;
            }

            $tipo = (string) (optional($ticket->tipoticket)->NombreTipo ?: 'Sin tipo');
            $sub = (string) (optional($ticket->subtipo)->NombreSubtipo ?: 'Sin subtipo');
            $ter = (string) (optional($ticket->tertipo)->NombreTertipo ?: 'Sin incidencia');
            $clave = "{$tipo}|{$sub}|{$ter}";

            if (!isset($tablaCategoriaDetallada[$clave])) {
                $tablaCategoriaDetallada[$clave] = [
                    'tipo' => $tipo,
                    'subtipo' => $sub,
                    'tertipo' => $ter,
                    'total' => 0,
                    'segundos' => [],
                ];
            }

            $tablaCategoriaDetallada[$clave]['total']++;

            if (!empty($ticket->FechaFinProgreso) && $ticket->Estatus === 'Cerrado') {
                try {
                    $tablaCategoriaDetallada[$clave]['segundos'][] = $ticketDate->diffInSeconds(Carbon::parse($ticket->FechaFinProgreso));
                } catch (\Exception $e) {
                }
            }
        }

        foreach ($tablaCategoriaDetallada as &$v) {
            $v['tiempo_prom'] = count($v['segundos']) > 0
                ? $this->formatSecondsToDays(array_sum($v['segundos']) / count($v['segundos']))
                : '—';
            $v['tiempo_prom_horas'] = count($v['segundos']) > 0
                ? $this->formatSecondsToHours(array_sum($v['segundos']) / count($v['segundos']))
                : 0;
        }
        unset($v);

        uasort(
            $tablaCategoriaDetallada,
            fn($a, $b) => strcmp($a['tipo'], $b['tipo'])
                ?: (strcmp($a['subtipo'], $b['subtipo']) ?: strcmp($a['tertipo'], $b['tertipo']))
        );

        foreach ($tablaCategoria as $key => $data) {
            $promRes = count($data['segundos_resolucion']) > 0
                ? array_sum($data['segundos_resolucion']) / count($data['segundos_resolucion'])
                : 0;

            $tablaCategoria[$key]['promedio_resolucion'] = $this->formatSecondsToDays($promRes);
        }

        $promedioNormales = count($segundosNormales) > 0 ? array_sum($segundosNormales) / count($segundosNormales) : 0;
        $promedioTotales = count($segundosTotales) > 0 ? array_sum($segundosTotales) / count($segundosTotales) : 0;
        $promedioPrimerRespuestaGeneral = count($segundosPrimerRespGenerales) > 0 ? array_sum($segundosPrimerRespGenerales) / count($segundosPrimerRespGenerales) : 0;
        $cumplimiento = $this->resumen['porcentaje_cumplimiento'] ?? 0;

        $promedioNormalesAnt = count($segundosNormalesAnt) > 0 ? array_sum($segundosNormalesAnt) / count($segundosNormalesAnt) : 0;
        $promedioTotalesAnt = count($segundosTotalesAnt) > 0 ? array_sum($segundosTotalesAnt) / count($segundosTotalesAnt) : 0;
        $promedioPrimerRespuestaGeneralAnt = count($segundosPrimerRespGeneralesAnt) > 0 ? array_sum($segundosPrimerRespGeneralesAnt) / count($segundosPrimerRespGeneralesAnt) : 0;
        $cumplimientoAnt = $totalTicketsMesAnterior > 0 ? round(($cerradosMesAnterior / $totalTicketsMesAnterior) * 100, 0) : 0;

        $totalesPorTipo = [];
        foreach ($tablaAgrupada as $tipo => $datos) {
            $total = array_sum($datos['total_principal'] ?? []);
            if ($total > 0) {
                $totalesPorTipo[$tipo] = $total;
            }
        }
        arsort($totalesPorTipo);

        $ticketsCerrados = (int) ($this->resumen['tickets_cerrados'] ?? $ticketsCerradosActualCalculado);

        $filasGerencia = [];
        foreach ($tablaAgrupada as $principal => $datos) {
            $filasGerencia[] = [
                'tipo' => 'padre',
                'nombre' => $principal,
                'totales' => $datos['total_principal'] ?? [],
            ];

            foreach (($datos['subtipos'] ?? []) as $subtipo => $datosSub) {
                $filasGerencia[] = [
                    'tipo' => 'hijo',
                    'nombre' => $subtipo,
                    'totales' => $datosSub['total_principal'] ?? [],
                ];
            }
        }

        $this->reportData = [
            'usuarios' => array_values($usuariosUnicos),
            'usuariosAmbosMeses' => array_values($usuariosAmbosMeses),
            'filasGerencia' => $filasGerencia,
            'tablaCategoriaDetallada' => array_values($tablaCategoriaDetallada),
            'tablaResponsableDetalle' => array_values($tablaResponsableDetalle),
            'tablaMesesUsuarios' => $tablaMesesUsuarios,
            'totalesPorTipo' => $totalesPorTipo,
            'ticketsSinClasificar' => $ticketsSinClasificar,
            'totalTickets' => $totalTicketsMesActual,
            'ticketsCerrados' => $ticketsCerrados,
            'porcentajeCerrados' => $totalTicketsMesActual > 0 ? round(($ticketsCerrados / $totalTicketsMesActual) * 100, 1) : 0,
            'mesActualCorto' => $mesActualCorto,
            'mesAnteriorCorto' => $mesAnteriorCorto,
            'mesNombreTarget' => $mesNombreTarget,
            'mesNombreAnterior' => $mesNombreAnterior,
            'promResolucionNormal' => $this->formatSecondsToDays($promedioNormales),
            'promResolucionTotal' => $this->formatSecondsToDays($promedioTotales),
            'promPrimerRespuesta' => $this->formatSecondsToDays($promedioPrimerRespuestaGeneral),
            'cumplimiento' => number_format((float) $cumplimiento, 0) . '%',
            'promResolucionNormalAnt' => $this->formatSecondsToDays($promedioNormalesAnt),
            'promResolucionTotalAnt' => $this->formatSecondsToDays($promedioTotalesAnt),
            'promPrimerRespuestaAnt' => $this->formatSecondsToDays($promedioPrimerRespuestaGeneralAnt),
            'cumplimientoAnt' => $cumplimientoAnt . '%',
            'textoAnormales' => 'Generalmente los tickets de duración anormal son aquellos que exceden el día laboral de duración (>8 hrs) y tiene que ver con falta de respuesta del que crea el ticket, incorrecta ejecución del proceso de atención (TI; principalmente en los primeros meses de la implementación del sistema), problema de múltiples respuestas o escalado.',
            'promResolucionHoras' => number_format($promedioTotales / 3600, 1),
            'promRespuestaHoras' => number_format($promedioPrimerRespuestaGeneral / 3600, 1),
        ];

        $this->prepared = true;
    }

    private function buildRows(): array
    {
        $d = $this->reportData;
        $rows = [];
        $row = 1;

        $rows[] = ['Tickets: Reporte de Productividad'];
        $this->layout['summary_title'] = $row++;
        $rows[] = ['Período: ' . ($d['mesNombreTarget'] ?? '')];
        $this->layout['summary_period'] = $row++;

        $rows[] = [
            'Total de Tickets',
            'Tickets Cerrados',
            'Tiempo Prom. Resolución',
            'Tiempo Prom. Respuesta',
            'Cumplimiento',
        ];
        $this->layout['summary_headers'] = $row++;

        $rows[] = [
            $d['totalTickets'] ?? 0,
            ($d['ticketsCerrados'] ?? 0) . "\n" . (($d['porcentajeCerrados'] ?? 0) . '% del total'),
            ($d['promResolucionHoras'] ?? '0') . "\n" . 'horas laborales',
            ($d['promRespuestaHoras'] ?? '0') . "\n" . 'horas laborales',
            $d['cumplimiento'] ?? '0%',
        ];
        $this->layout['summary_values'] = $row++;

        $rows[] = [];
        $row++;

        if (!empty($d['totalesPorTipo'])) {
            $this->layout['resumen_tipo'] = [
                'title' => $row,
                'header' => $row + 1,
                'dataRows' => [],
            ];

            $rows[] = ['Tickets: Resumen por Tipo'];
            $row++;

            $rows[] = ['Tipo', 'Total'];
            $row++;

            foreach ($d['totalesPorTipo'] as $tipo => $total) {
                $rows[] = [$tipo, $total];
                $this->layout['resumen_tipo']['dataRows'][] = $row;
                $row++;
            }

            $this->layout['resumen_tipo']['end'] = $row - 1;

            $rows[] = [];
            $row++;
        }

        if (($d['ticketsSinClasificar'] ?? 0) > 0) {
            $rows[] = ['Nota: ' . $d['ticketsSinClasificar'] . ' ticket(s) sin tipo asignado en este período.'];
            $this->layout['nota_sin_tipo'] = $row;
            $row++;

            $rows[] = [];
            $row++;
        }

        $usuarios = $d['usuarios'] ?? [];

        $this->layout['incidencias'] = [
            'title' => $row,
            'header' => $row + 1,
            'dataRows' => [],
            'endCol' => 2 + count($usuarios),
        ];

        $rows[] = array_merge(
            ['Tickets:Incidencias por gerencia por usuario asignado'],
            array_fill(0, count($usuarios) + 1, '')
        );
        $row++;

        $rows[] = array_merge(['Etiquetas de fila', 'Total general'], $usuarios);
        $row++;

        foreach ($d['filasGerencia'] as $filaActual) {
            $esPadre = $filaActual['tipo'] === 'padre';
            $prefijo = $esPadre ? '1- ' : '2- ';
            $totalFila = 0;

            foreach ($usuarios as $usr) {
                $totalFila += (int) ($filaActual['totales'][$usr] ?? 0);
            }

            $fila = [$prefijo . $filaActual['nombre'], $totalFila > 0 ? $totalFila : ''];

            foreach ($usuarios as $usr) {
                $valor = $filaActual['totales'][$usr] ?? 0;
                $fila[] = $valor > 0 ? $valor : '';
            }

            $rows[] = $fila;
            $this->layout['incidencias']['dataRows'][] = [
                'row' => $row,
                'type' => $filaActual['tipo'],
            ];
            $row++;
        }

        $granTotal = 0;
        foreach ($d['filasGerencia'] as $filaActual) {
            if ($filaActual['tipo'] !== 'padre') {
                continue;
            }

            foreach ($usuarios as $usr) {
                $granTotal += (int) ($filaActual['totales'][$usr] ?? 0);
            }
        }

        $filaTotal = ['Total general', $granTotal];
        foreach ($usuarios as $usr) {
            $totUsr = 0;
            foreach ($d['filasGerencia'] as $filaActual) {
                if ($filaActual['tipo'] === 'padre') {
                    $totUsr += (int) ($filaActual['totales'][$usr] ?? 0);
                }
            }
            $filaTotal[] = $totUsr > 0 ? $totUsr : '';
        }

        $rows[] = $filaTotal;
        $this->layout['incidencias']['totalRow'] = $row;
        $this->layout['incidencias']['end'] = $row;
        $row++;

        $rows[] = [];
        $row++;

        $this->layout['categorias'] = [
            'title' => $row,
            'header' => $row + 1,
            'dataRows' => [],
        ];

        $rows[] = ['Tickets: Incidencias por categoría — ' . ($d['mesNombreTarget'] ?? '')];
        $row++;

        $rows[] = ['Tipo', 'Subtipo', 'Incidencia', 'Cuenta', 'Tiempo Prom. Resolución'];
        $row++;

        foreach ($d['tablaCategoriaDetallada'] as $fila) {
            $rows[] = [
                $fila['tipo'],
                $fila['subtipo'],
                $fila['tertipo'],
                $fila['total'],
                $fila['tiempo_prom'] ?? '—',
            ];
            $this->layout['categorias']['dataRows'][] = $row;
            $row++;
        }

        $rows[] = ['Total general', '', '', $d['totalTickets'] ?? 0, ''];
        $this->layout['categorias']['totalRow'] = $row;
        $this->layout['categorias']['end'] = $row;
        $row++;

        $rows[] = [];
        $row++;

        if (!empty($d['tablaResponsableDetalle'])) {
            $this->layout['responsables'] = [
                'title' => $row,
                'header' => $row + 1,
                'dataRows' => [],
            ];

            $rows[] = ['Tickets: Resumen por Responsable — Tipo · Subtipo · Incidencia'];
            $row++;

            $rows[] = ['Responsable', 'Tipo', 'Subtipo', 'Incidencia', 'Total', 'Tiempo Prom.'];
            $row++;

            $responsableActual = null;
            foreach ($d['tablaResponsableDetalle'] as $fila) {
                $mostrarResponsable = ($responsableActual !== $fila['responsable']);
                $responsableActual = $fila['responsable'];

                $rows[] = [
                    $mostrarResponsable ? $fila['responsable'] : '',
                    $fila['tipo'],
                    $fila['subtipo'],
                    $fila['tertipo'],
                    $fila['total'],
                    $fila['tiempo_prom'] ?? '—',
                ];
                $this->layout['responsables']['dataRows'][] = $row;
                $row++;
            }

            $this->layout['responsables']['end'] = $row - 1;

            $rows[] = [];
            $row++;
        }

        $this->layout['tiempos'] = [
            'actualTitle' => $row,
            'actualRows' => [$row + 1, $row + 2, $row + 3, $row + 4],
            'prevTitle' => $row + 6,
            'prevRows' => [$row + 7, $row + 8, $row + 9, $row + 10],
            'noteRow' => $row + 11,
        ];

        $rows[] = ['Tickets: Tiempos de respuesta promedio — ' . ($d['mesNombreTarget'] ?? '')];
        $row++;

        $rows[] = ['Tiempo Promedio de primer respuesta', $d['promPrimerRespuesta'], 'Tiempo de respuesta promedio de tickets normales'];
        $row++;
        $rows[] = ['Tiempo promedio de resolución', $d['promResolucionNormal'], 'Tiempo de resolución promedio (tickets menores a 8 horas)'];
        $row++;
        $rows[] = ['Tiempo promedio Total', $d['promResolucionTotal'], 'Tiempo total (incluyendo tickets de duración anormal)'];
        $row++;
        $rows[] = ['Porcentaje de cumplimiento', $d['cumplimiento'], $this->truncateWords($d['textoAnormales'], 10) . '...'];
        $row++;

        $rows[] = [];
        $row++;

        $rows[] = ['Tickets: Tiempos de respuesta promedio — ' . ($d['mesNombreAnterior'] ?? '')];
        $row++;

        $rows[] = ['Tiempo Promedio de primer respuesta', $d['promPrimerRespuestaAnt'], 'Tiempo de respuesta promedio de tickets normales'];
        $row++;
        $rows[] = ['Tiempo promedio de resolución', $d['promResolucionNormalAnt'], 'Tiempo de resolución promedio (tickets menores a 8 horas)'];
        $row++;
        $rows[] = ['Tiempo promedio Total', $d['promResolucionTotalAnt'], 'Tiempo total (incluyendo tickets de duración anormal)'];
        $row++;
        $rows[] = ['Porcentaje de cumplimiento', $d['cumplimientoAnt'], $this->truncateWords($d['textoAnormales'], 10) . '...'];
        $row++;

        $rows[] = ['"' . $d['textoAnormales'] . '"'];
        $row++;

        $rows[] = [];
        $row++;

        $this->layout['comparativo'] = [
            'title' => $row,
            'header' => $row + 1,
            'dataRows' => [],
        ];

        $rows[] = ['Tickets: Comparativo de meses por usuario'];
        $row++;

        $rows[] = ['Etiquetas de fila', $d['mesAnteriorCorto'], $d['mesActualCorto'], 'Total general'];
        $row++;

        foreach ($d['usuariosAmbosMeses'] as $usr) {
            $valAnt = $d['tablaMesesUsuarios'][$usr][$d['mesAnteriorCorto']] ?? 0;
            $valAct = $d['tablaMesesUsuarios'][$usr][$d['mesActualCorto']] ?? 0;

            $rows[] = [
                $usr,
                $valAnt > 0 ? $valAnt : '',
                $valAct > 0 ? $valAct : '',
                $valAnt + $valAct,
            ];

            $this->layout['comparativo']['dataRows'][] = $row;
            $row++;
        }

        $this->layout['comparativo']['end'] = $row - 1;

        // ========== TABLAS DE SOLICITUDES ==========
        if (!empty($this->metricasSolicitudes) && !empty($this->metricasSolicitudes['desglose'])) {
            $desglose = $this->metricasSolicitudes['desglose'];
            $promedioCotizacion = $this->metricasSolicitudes['promedio_cotizacion_horas'] ?? 0;
            $promedioConfiguracion = $this->metricasSolicitudes['promedio_configuracion_dias'] ?? 0;
            $promedioTotal = ($promedioCotizacion + $promedioConfiguracion);

            $rows[] = [];
            $row++;

            // Tabla 1: Resumen de promedios por Gerencia
            $this->layout['solicitudes_gerencia'] = [
                'start' => $row,
                'headers' => $row + 1,
                'dataStart' => $row + 2,
            ];

            $rows[] = ['Solicitudes: Tiempos promedio por Gerencia — ' . ($d['mesNombreTarget'] ?? '')];
            $row++;

            $rows[] = ['Gerencia', 'Cantidad', 'Prom. Cotización (h)', 'Prom. Configuración (h)', 'Prom. Total (h)'];
            $row++;

            // Agrupar por gerencia
            $porGerencia = [];
            foreach ($desglose as $sol) {
                $gerencia = $sol['gerencia_nombre'] ?? 'Sin Gerencia';
                if (!isset($porGerencia[$gerencia])) {
                    $porGerencia[$gerencia] = [
                        'cantidad' => 0,
                        'suma_cotizacion' => 0,
                        'suma_configuracion' => 0,
                        'suma_total' => 0,
                        'count_cotizacion' => 0,
                        'count_configuracion' => 0,
                        'count_total' => 0,
                    ];
                }
                $porGerencia[$gerencia]['cantidad']++;
                
                if (($sol['tiempo_cotizacion_horas'] ?? null) !== null) {
                    $porGerencia[$gerencia]['suma_cotizacion'] += $sol['tiempo_cotizacion_horas'];
                    $porGerencia[$gerencia]['count_cotizacion']++;
                }
                if (($sol['tiempo_configuracion_dias'] ?? null) !== null) {
                    $porGerencia[$gerencia]['suma_configuracion'] += $sol['tiempo_configuracion_dias'];
                    $porGerencia[$gerencia]['count_configuracion']++;
                }
                if (($sol['tiempo_total_dias'] ?? null) !== null) {
                    $porGerencia[$gerencia]['suma_total'] += $sol['tiempo_total_dias'];
                    $porGerencia[$gerencia]['count_total']++;
                }
            }

            ksort($porGerencia);

            foreach ($porGerencia as $gerencia => $data) {
                $promCot = $data['count_cotizacion'] > 0 ? round($data['suma_cotizacion'] / $data['count_cotizacion'], 1) : 0;
                $promConf = $data['count_configuracion'] > 0 ? round($data['suma_configuracion'] / $data['count_configuracion'], 1) : 0;
                $promTot = $data['count_total'] > 0 ? round($data['suma_total'] / $data['count_total'], 1) : 0;
                
                $rows[] = [
                    $gerencia,
                    $data['cantidad'],
                    $promCot,
                    $promConf,
                    $promTot,
                ];
                $row++;
            }

            $rows[] = [
                'Total General',
                count($desglose),
                round($promedioCotizacion, 1),
                round($promedioConfiguracion, 1),
                round($promedioTotal, 1),
            ];
            $this->layout['solicitudes_gerencia']['totalRow'] = $row;
            $this->layout['solicitudes_gerencia']['end'] = $row;
            $row++;

            $rows[] = [];
            $row++;

            // Tabla 2: Resumen por Motivo
            $this->layout['solicitudes_motivo'] = [
                'start' => $row,
                'headers' => $row + 1,
                'dataStart' => $row + 2,
            ];

            $rows[] = ['Solicitudes: Tiempos promedio por Motivo — ' . ($d['mesNombreTarget'] ?? '')];
            $row++;

            $rows[] = ['Motivo', 'Cantidad', 'Prom. Cotización (h)', 'Prom. Configuración (h)', 'Prom. Total (h)'];
            $row++;

            // Agrupar por motivo
            $porMotivo = [];
            foreach ($desglose as $sol) {
                $motivo = $sol['motivo'] ?? 'Sin Motivo';
                if (!isset($porMotivo[$motivo])) {
                    $porMotivo[$motivo] = [
                        'cantidad' => 0,
                        'suma_cotizacion' => 0,
                        'suma_configuracion' => 0,
                        'suma_total' => 0,
                        'count_cotizacion' => 0,
                        'count_configuracion' => 0,
                        'count_total' => 0,
                    ];
                }
                $porMotivo[$motivo]['cantidad']++;
                
                if (($sol['tiempo_cotizacion_horas'] ?? null) !== null) {
                    $porMotivo[$motivo]['suma_cotizacion'] += $sol['tiempo_cotizacion_horas'];
                    $porMotivo[$motivo]['count_cotizacion']++;
                }
                if (($sol['tiempo_configuracion_dias'] ?? null) !== null) {
                    $porMotivo[$motivo]['suma_configuracion'] += $sol['tiempo_configuracion_dias'];
                    $porMotivo[$motivo]['count_configuracion']++;
                }
                if (($sol['tiempo_total_dias'] ?? null) !== null) {
                    $porMotivo[$motivo]['suma_total'] += $sol['tiempo_total_dias'];
                    $porMotivo[$motivo]['count_total']++;
                }
            }

            // Ordenar por cantidad descendente
            uasort($porMotivo, fn($a, $b) => $b['cantidad'] <=> $a['cantidad']);

            foreach ($porMotivo as $motivo => $data) {
                $promCot = $data['count_cotizacion'] > 0 ? round($data['suma_cotizacion'] / $data['count_cotizacion'], 1) : 0;
                $promConf = $data['count_configuracion'] > 0 ? round($data['suma_configuracion'] / $data['count_configuracion'], 1) : 0;
                $promTot = $data['count_total'] > 0 ? round($data['suma_total'] / $data['count_total'], 1) : 0;
                
                $rows[] = [
                    $motivo,
                    $data['cantidad'],
                    $promCot,
                    $promConf,
                    $promTot,
                ];
                $row++;
            }

            $rows[] = [
                'Total General',
                count($desglose),
                round($promedioCotizacion, 1),
                round($promedioConfiguracion, 1),
                round($promedioTotal, 1),
            ];
            $this->layout['solicitudes_motivo']['totalRow'] = $row;
            $this->layout['solicitudes_motivo']['end'] = $row;
            $row++;

            $rows[] = [];
            $row++;

            // Tabla 3: Comparación Cotización vs Configuración
            $this->layout['solicitudes_comparacion'] = [
                'start' => $row,
                'headers' => $row + 1,
                'dataStart' => $row + 2,
            ];

            $rows[] = ['Solicitudes: Comparación de Tiempos Cotización vs Configuración — ' . ($d['mesNombreTarget'] ?? '')];
            $row++;

            $rows[] = ['Métrica', 'Promedio (h)', 'Mínimo (h)', 'Máximo (h)', '% del Total'];
            $row++;

            // Calcular estadísticas de cotización
            $tiemposCotizacion = [];
            $tiemposConfiguracion = [];
            foreach ($desglose as $sol) {
                if (($sol['tiempo_cotizacion_horas'] ?? null) !== null) {
                    $tiemposCotizacion[] = $sol['tiempo_cotizacion_horas'];
                }
                if (($sol['tiempo_configuracion_dias'] ?? null) !== null) {
                    $tiemposConfiguracion[] = $sol['tiempo_configuracion_dias'];
                }
            }

            $promCot = count($tiemposCotizacion) > 0 ? round(array_sum($tiemposCotizacion) / count($tiemposCotizacion), 1) : 0;
            $minCot = count($tiemposCotizacion) > 0 ? round(min($tiemposCotizacion), 1) : 0;
            $maxCot = count($tiemposCotizacion) > 0 ? round(max($tiemposCotizacion), 1) : 0;

            $promConf = count($tiemposConfiguracion) > 0 ? round(array_sum($tiemposConfiguracion) / count($tiemposConfiguracion), 1) : 0;
            $minConf = count($tiemposConfiguracion) > 0 ? round(min($tiemposConfiguracion), 1) : 0;
            $maxConf = count($tiemposConfiguracion) > 0 ? round(max($tiemposConfiguracion), 1) : 0;

            $totalPromedio = $promCot + $promConf;
            $porcentajeCot = $totalPromedio > 0 ? round(($promCot / $totalPromedio) * 100, 1) : 0;
            $porcentajeConf = $totalPromedio > 0 ? round(($promConf / $totalPromedio) * 100, 1) : 0;

            // Fila Cotización
            $rows[] = [
                'Tiempo de Cotización',
                $promCot,
                $minCot,
                $maxCot,
                $porcentajeCot > 0 ? $porcentajeCot . '%' : '0%',
            ];
            $row++;

            // Fila Configuración
            $rows[] = [
                'Tiempo de Configuración',
                $promConf,
                $minConf,
                $maxConf,
                $porcentajeConf > 0 ? $porcentajeConf . '%' : '0%',
            ];
            $row++;

            // Fila Total
            $rows[] = [
                'Tiempo Total',
                round($totalPromedio, 1),
                round($minCot + $minConf, 1),
                round($maxCot + $maxConf, 1),
                '100%',
            ];
            $this->layout['solicitudes_comparacion']['totalRow'] = $row;
            $this->layout['solicitudes_comparacion']['end'] = $row;
            $row++;
        }

        return $rows;
    }

    private function normalizeRows(array $rows): array
    {
        $max = 1;

        foreach ($rows as $row) {
            $max = max($max, count($row));
        }

        $this->maxColumns = max(6, $max);

        foreach ($rows as &$row) {
            $row = array_pad($row, $this->maxColumns, '');
        }
        unset($row);

        return $rows;
    }

    private function formatSecondsToDays($seconds): string
    {
        if (!$seconds || $seconds <= 0) {
            return '0.00:00:00';
        }

        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%d.%02d:%02d:%02d', $days, $hours, $minutes, $secs);
    }

    private function formatSecondsToHours($seconds): float
    {
        if (!$seconds || $seconds <= 0) {
            return 0;
        }

        return round($seconds / 3600, 2);
    }

    private function truncateWords(string $text, int $count): string
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];

        if (count($words) <= $count) {
            return trim($text);
        }

        return implode(' ', array_slice($words, 0, $count));
    }

    private function col(int $index): string
    {
        return Coordinate::stringFromColumnIndex($index);
    }

    public function getCharts(): array
    {
        return $this->getChartsForSheet($this->title());
    }

    public function getChartsForSheet(string $sheetName): array
    {
        $this->prepareReportData();

        if (!isset($this->layout['resumen_tipo'])) {
            $this->normalizeRows($this->buildRows());
        }

        $charts = [];
        
        // Layout en cuadrícula 2x3 (Dashboard style)
        // Columna Izquierda: A-I, Columna Derecha: K-S
        $leftCol = 'A';
        $rightCol = 'K';
        $rowHeight = 22; // Espacio entre filas de gráficas
        
        // FILA 1: Tickets - Resumen General
        $row1 = 2;
        
        // FILA 2: Tickets - Detalles
        $row2 = $row1 + $rowHeight;
        
        // FILA 3: Solicitudes
        $row3 = $row2 + $rowHeight;

        // === FILA 1: RESUMEN DE TICKETS ===
        
        // Gráfica 1 (Izquierda): Resumen por Tipo
        if (
            isset($this->layout['resumen_tipo']) &&
            !empty($this->reportData['totalesPorTipo']) &&
            !empty($this->layout['resumen_tipo']['dataRows'])
        ) {
            $chart = $this->createChartResumenPorTipo($sheetName, $leftCol, $row1);
            $charts[] = $chart;
        }

        // Gráfica 2 (Derecha): Incidencias por Categoría
        if (
            isset($this->layout['categorias']) &&
            !empty($this->reportData['tablaCategoriaDetallada']) &&
            !empty($this->layout['categorias']['dataRows'])
        ) {
            $chart = $this->createChartIncidenciasPorCategoria($sheetName, $rightCol, $row1);
            $charts[] = $chart;
        }

        // === FILA 2: ANÁLISIS DETALLADO ===
        
        // Gráfica 3 (Izquierda): Incidencias por Gerencia
        if (
            isset($this->layout['incidencias']) &&
            !empty($this->layout['incidencias']['dataRows'])
        ) {
            $chart = $this->createChartIncidenciasPorGerencia($sheetName, $leftCol, $row2);
            $charts[] = $chart;
        }

        // Gráfica 4 (Derecha): Comparativo de Meses
        if (
            isset($this->layout['comparativo']) &&
            !empty($this->reportData['usuariosAmbosMeses']) &&
            !empty($this->layout['comparativo']['dataRows'])
        ) {
            $chart = $this->createChartComparativoMeses($sheetName, $rightCol, $row2);
            $charts[] = $chart;
        }

        // === FILA 3: SOLICITUDES ===
        
        // Gráfica 5 (Izquierda): Solicitudes por Gerencia
        if (
            isset($this->layout['solicitudes_gerencia']) &&
            !empty($this->metricasSolicitudes)
        ) {
            $chart = $this->createChartSolicitudesPorGerencia($sheetName, $leftCol, $row3);
            $charts[] = $chart;
        }

        // Gráfica 6 (Derecha): Solicitudes por Motivo
        if (
            isset($this->layout['solicitudes_motivo']) &&
            !empty($this->metricasSolicitudes)
        ) {
            $chart = $this->createChartSolicitudesPorMotivo($sheetName, $rightCol, $row3);
            $charts[] = $chart;
        }

        return $charts;
    }

    private function createChartResumenPorTipo(string $sheetName, string $baseCol, int $baseRow): Chart
    {
        $headerRow = $this->layout['resumen_tipo']['header'];
        $firstDataRow = $headerRow + 1;
        $dataCount = count($this->layout['resumen_tipo']['dataRows']);
        $endRow = $firstDataRow + $dataCount - 1;

        $labels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "'{$sheetName}'!\$B\${$headerRow}",
                null,
                1
            ),
        ];

        $categories = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "'{$sheetName}'!\$A\${$firstDataRow}:\$A\${$endRow}",
                null,
                $dataCount
            ),
        ];

        $values = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                "'{$sheetName}'!\$B\${$firstDataRow}:\$B\${$endRow}",
                null,
                $dataCount
            ),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_STANDARD,
            range(0, count($values) - 1),
            $labels,
            $categories,
            $values
        );

        $series->setPlotDirection(DataSeries::DIRECTION_BAR);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT);

        $chart = new Chart(
            'resumen_por_tipo',
            new Title('TICKETS: RESUMEN POR TIPO'),
            $legend,
            $plotArea
        );

        $chart->setTopLeftCell($baseCol . $baseRow);
        $endCol = ($baseCol === 'A') ? 'J' : 'T';
        $chart->setBottomRightCell($endCol . ($baseRow + 20));

        return $chart;
    }

    private function createChartIncidenciasPorCategoria(string $sheetName, string $baseCol, int $baseRow): Chart
    {
        $headerRow = $this->layout['categorias']['header'];
        $firstDataRow = $headerRow + 1;
        $dataCount = count($this->layout['categorias']['dataRows']);
        $endRow = $firstDataRow + $dataCount - 1;

        $labels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "'{$sheetName}'!\$D\${$headerRow}",
                null,
                1
            ),
        ];

        $categories = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "'{$sheetName}'!\$C\${$firstDataRow}:\$C\${$endRow}",
                null,
                $dataCount
            ),
        ];

        $values = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                "'{$sheetName}'!\$D\${$firstDataRow}:\$D\${$endRow}",
                null,
                $dataCount
            ),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_STANDARD,
            range(0, count($values) - 1),
            $labels,
            $categories,
            $values
        );

        $series->setPlotDirection(DataSeries::DIRECTION_BAR);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT);

        $chart = new Chart(
            'incidencias_por_categoria',
            new Title('TICKETS: DISTRIBUCIÓN POR CATEGORÍA'),
            $legend,
            $plotArea
        );

        $chart->setTopLeftCell($baseCol . $baseRow);
        $endCol = ($baseCol === 'A') ? 'J' : 'T';
        $chart->setBottomRightCell($endCol . ($baseRow + 20));

        return $chart;
    }

    private function createChartIncidenciasPorGerencia(string $sheetName, string $baseCol, int $baseRow): Chart
    {
        $headerRow = $this->layout['incidencias']['header'];
        $firstDataRow = $headerRow + 1;
        $dataCount = count($this->layout['incidencias']['dataRows']);
        $endRow = $firstDataRow + $dataCount - 1;

        $usuarios = $this->reportData['usuarios'] ?? [];
        $numUsuarios = count($usuarios);

        $categories = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "'{$sheetName}'!\$A\${$firstDataRow}:\$A\${$endRow}",
                null,
                $dataCount
            ),
        ];

        $values = [];
        $labels = [];

        foreach ($usuarios as $index => $usuario) {
            $colIndex = 3 + $index; // Columnac C, D, E, etc.
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);

            $labels[] = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "'{$sheetName}'!\${$colLetter}\${$headerRow}",
                null,
                1
            );

            $values[] = new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                "'{$sheetName}'!\${$colLetter}\${$firstDataRow}:\${$colLetter}\${$endRow}",
                null,
                $dataCount
            );
        }

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_STACKED,
            range(0, count($values) - 1),
            $labels,
            $categories,
            $values
        );

        $series->setPlotDirection(DataSeries::DIRECTION_BAR);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT);

        $chart = new Chart(
            'incidencias_por_gerencia',
            new Title('TICKETS: INCIDENCIAS POR GERENCIA Y USUARIO'),
            $legend,
            $plotArea
        );

        $chart->setTopLeftCell($baseCol . $baseRow);
        $endCol = ($baseCol === 'A') ? 'J' : 'T';
        $chart->setBottomRightCell($endCol . ($baseRow + 20));

        return $chart;
    }

    private function createChartComparativoMeses(string $sheetName, string $baseCol, int $baseRow): Chart
    {
        $headerRow = $this->layout['comparativo']['header'];
        $firstDataRow = $headerRow + 1;
        $dataCount = count($this->layout['comparativo']['dataRows']);
        $endRow = $firstDataRow + $dataCount - 1;

        $mesAnterior = $this->reportData['mesAnteriorCorto'] ?? '';
        $mesActual = $this->reportData['mesActualCorto'] ?? '';

        $labels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "'{$sheetName}'!\$B\${$headerRow}",
                null,
                1
            ),
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "'{$sheetName}'!\$C\${$headerRow}",
                null,
                1
            ),
        ];

        $categories = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "'{$sheetName}'!\$A\${$firstDataRow}:\$A\${$endRow}",
                null,
                $dataCount
            ),
        ];

        $values = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                "'{$sheetName}'!\$B\${$firstDataRow}:\$B\${$endRow}",
                null,
                $dataCount
            ),
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                "'{$sheetName}'!\$C\${$firstDataRow}:\$C\${$endRow}",
                null,
                $dataCount
            ),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            $labels,
            $categories,
            $values
        );

        $series->setPlotDirection(DataSeries::DIRECTION_BAR);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT);

        $chart = new Chart(
            'comparativo_meses',
            new Title('TICKETS: ANÁLISIS COMPARATIVO MENSUAL'),
            $legend,
            $plotArea
        );

        $chart->setTopLeftCell($baseCol . $baseRow);
        $endCol = ($baseCol === 'A') ? 'J' : 'T';
        $chart->setBottomRightCell($endCol . ($baseRow + 20));

        return $chart;
    }

    private function createChartSolicitudesPorGerencia(string $sheetName, string $baseCol, int $baseRow): Chart
    {
        $headerRow = $this->layout['solicitudes_gerencia']['headers'];
        $firstDataRow = $this->layout['solicitudes_gerencia']['dataStart'];
        $totalRow = $this->layout['solicitudes_gerencia']['totalRow'];
        $endRow = $totalRow - 1; // Excluir fila de total

        $labels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "'" . $sheetName . "'!\$B\$" . $headerRow,
                null,
                1
            ),
        ];

        $categories = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "'" . $sheetName . "'!\$A\$" . $firstDataRow . ":\$A\$" . $endRow,
                null,
                $endRow - $firstDataRow + 1
            ),
        ];

        $values = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                "'" . $sheetName . "'!\$B\$" . $firstDataRow . ":\$B\$" . $endRow,
                null,
                $endRow - $firstDataRow + 1
            ),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_STANDARD,
            [0],
            $labels,
            $categories,
            $values
        );

        $series->setPlotDirection(DataSeries::DIRECTION_BAR);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT);

        $chart = new Chart(
            'solicitudes_gerencia',
            new Title('SOLICITUDES: ANÁLISIS POR GERENCIA'),
            $legend,
            $plotArea
        );

        $chart->setTopLeftCell($baseCol . $baseRow);
        $endCol = ($baseCol === 'A') ? 'J' : 'T';
        $chart->setBottomRightCell($endCol . ($baseRow + 20));

        return $chart;
    }

    private function createChartSolicitudesPorMotivo(string $sheetName, string $baseCol, int $baseRow): Chart
    {
        $headerRow = $this->layout['solicitudes_motivo']['headers'];
        $firstDataRow = $this->layout['solicitudes_motivo']['dataStart'];
        $totalRow = $this->layout['solicitudes_motivo']['totalRow'];
        $endRow = $totalRow - 1; // Excluir fila de total

        $labels = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "'" . $sheetName . "'!\$B\$" . $headerRow,
                null,
                1
            ),
        ];

        $categories = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_STRING,
                "'" . $sheetName . "'!\$A\$" . $firstDataRow . ":\$A\$" . $endRow,
                null,
                $endRow - $firstDataRow + 1
            ),
        ];

        $values = [
            new DataSeriesValues(
                DataSeriesValues::DATASERIES_TYPE_NUMBER,
                "'" . $sheetName . "'!\$B\$" . $firstDataRow . ":\$B\$" . $endRow,
                null,
                $endRow - $firstDataRow + 1
            ),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_STANDARD,
            [0],
            $labels,
            $categories,
            $values
        );

        $series->setPlotDirection(DataSeries::DIRECTION_BAR);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT);

        $chart = new Chart(
            'solicitudes_motivo',
            new Title('SOLICITUDES: DISTRIBUCIÓN POR MOTIVO'),
            $legend,
            $plotArea
        );

        $chart->setTopLeftCell($baseCol . $baseRow);
        $endCol = ($baseCol === 'A') ? 'J' : 'T';
        $chart->setBottomRightCell($endCol . ($baseRow + 20));

        return $chart;
    }

    private function getStyles(): array
    {
        $primaryBlue = '0066CC';
        $darkBlue = '003D99';
        $lightBlue = 'E6F2FF';
        $successGreen = '27AE60';
        $darkGreen = '1E8449';
        $lightGreen = 'D5F4E6';
        $warningOrange = 'E67E22';
        $darkOrange = 'D35400';
        $lightOrange = 'FCE8D0';
        $slate = '2C3E50';
        $lightSlate = 'ECF0F1';
        $white = 'FFFFFF';
        $borderColor = 'D1D5DB';

        return [
            'section_title' => [
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['rgb' => $white],
                    'name' => 'Segoe UI',
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => $primaryBlue],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => $darkBlue],
                    ],
                ],
            ],
            'main_title' => [
                'font' => [
                    'bold' => true,
                    'size' => 22,
                    'color' => ['rgb' => $white],
                    'name' => 'Segoe UI',
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => $darkBlue],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => $primaryBlue],
                    ],
                ],
            ],
            'period' => [
                'font' => [
                    'bold' => true,
                    'size' => 13,
                    'color' => ['rgb' => $darkBlue],
                    'name' => 'Segoe UI',
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => $lightBlue],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => $borderColor],
                    ],
                ],
            ],
            'card_header_blue' => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $white], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $primaryBlue]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'card_value_blue' => [
                'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => $slate], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $lightBlue]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'card_header_green' => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $white], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $successGreen]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'card_value_green' => [
                'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => $slate], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $lightGreen]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'card_header_purple' => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $white], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '8B5CF6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'card_value_purple' => [
                'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => $slate], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F3E8FF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'card_header_amber' => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $white], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $warningOrange]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'card_value_amber' => [
                'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => $slate], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $lightOrange]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'table_header' => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $white], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $primaryBlue]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'table_header_left' => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $white], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $primaryBlue]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'normal' => [
                'font' => ['size' => 10, 'color' => ['rgb' => $slate], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $white]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'normal_alt' => [
                'font' => ['size' => 10, 'color' => ['rgb' => $slate], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $lightSlate]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'center' => [
                'font' => ['size' => 10, 'color' => ['rgb' => $slate], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $white]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'center_alt' => [
                'font' => ['size' => 10, 'color' => ['rgb' => $slate], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $lightSlate]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'label' => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $slate], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $lightSlate]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'note' => [
                'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555'], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F9F9F9']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'green_row' => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $white], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $successGreen]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'green_center' => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $white], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $successGreen]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'parent_row' => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $white], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $primaryBlue]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $darkBlue]]],
            ],
            'child_row' => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $slate], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F0F4F8']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
            'total_row' => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $white], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $darkBlue]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $primaryBlue]]],
            ],
            'warning' => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $white], 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $warningOrange]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $borderColor]]],
            ],
        ];
    }

    private function applyStyle(Worksheet $sheet, string $range, array $style): void
    {
        $sheet->getStyle($range)->applyFromArray($style);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->prepareReportData();

                $sheet = $event->sheet->getDelegate();
                $styles = $this->getStyles();

                // Sin datos auxiliares de tiempos

                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(11);
                $sheet->setShowGridlines(false);

                $sheet->getDefaultRowDimension()->setRowHeight(20);
                $sheet->getRowDimension($this->layout['summary_title'])->setRowHeight(28);
                $sheet->getRowDimension($this->layout['summary_period'])->setRowHeight(22);
                $sheet->getRowDimension($this->layout['summary_headers'])->setRowHeight(32);
                $sheet->getRowDimension($this->layout['summary_values'])->setRowHeight(36);

                $sheet->getColumnDimension('A')->setWidth(40);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(24);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(24);
                $sheet->getColumnDimension('F')->setWidth(20);

                for ($i = 7; $i <= $this->maxColumns; $i++) {
                    $sheet->getColumnDimension($this->col($i))->setWidth(18);
                }

                $sheet->mergeCells('A' . $this->layout['summary_title'] . ':E' . $this->layout['summary_title']);
                $sheet->mergeCells('A' . $this->layout['summary_period'] . ':E' . $this->layout['summary_period']);

                $this->applyStyle($sheet, 'A' . $this->layout['summary_title'] . ':E' . $this->layout['summary_title'], $styles['main_title']);
                $this->applyStyle($sheet, 'A' . $this->layout['summary_period'] . ':E' . $this->layout['summary_period'], $styles['period']);

                $this->applyStyle($sheet, 'A' . $this->layout['summary_headers'], $styles['card_header_blue']);
                $this->applyStyle($sheet, 'A' . $this->layout['summary_values'], $styles['card_value_blue']);

                $this->applyStyle($sheet, 'B' . $this->layout['summary_headers'], $styles['card_header_green']);
                $this->applyStyle($sheet, 'B' . $this->layout['summary_values'], $styles['card_value_green']);

                $this->applyStyle($sheet, 'C' . $this->layout['summary_headers'], $styles['card_header_purple']);
                $this->applyStyle($sheet, 'C' . $this->layout['summary_values'], $styles['card_value_purple']);

                $this->applyStyle($sheet, 'D' . $this->layout['summary_headers'], $styles['card_header_amber']);
                $this->applyStyle($sheet, 'D' . $this->layout['summary_values'], $styles['card_value_amber']);

                $this->applyStyle($sheet, 'E' . $this->layout['summary_headers'], $styles['card_header_green']);
                $this->applyStyle($sheet, 'E' . $this->layout['summary_values'], $styles['card_value_green']);

                if (isset($this->layout['resumen_tipo'])) {
                    $sheet->mergeCells('A' . $this->layout['resumen_tipo']['title'] . ':B' . $this->layout['resumen_tipo']['title']);
                    $this->applyStyle($sheet, 'A' . $this->layout['resumen_tipo']['title'] . ':B' . $this->layout['resumen_tipo']['title'], $styles['section_title']);
                    $this->applyStyle($sheet, 'A' . $this->layout['resumen_tipo']['header'], $styles['table_header_left']);
                    $this->applyStyle($sheet, 'B' . $this->layout['resumen_tipo']['header'], $styles['table_header']);

                    foreach ($this->layout['resumen_tipo']['dataRows'] as $index => $row) {
                        $styleKey = ($index % 2 === 0) ? 'normal' : 'normal_alt';
                        $centerKey = ($index % 2 === 0) ? 'center' : 'center_alt';
                        $this->applyStyle($sheet, "A{$row}", $styles[$styleKey]);
                        $this->applyStyle($sheet, "B{$row}", $styles[$centerKey]);
                    }
                }

                if (isset($this->layout['nota_sin_tipo'])) {
                    $sheet->mergeCells('A' . $this->layout['nota_sin_tipo'] . ':E' . $this->layout['nota_sin_tipo']);
                    $this->applyStyle($sheet, 'A' . $this->layout['nota_sin_tipo'] . ':E' . $this->layout['nota_sin_tipo'], $styles['warning']);
                }

                $incEndCol = $this->col($this->layout['incidencias']['endCol']);
                $sheet->mergeCells('A' . $this->layout['incidencias']['title'] . ':' . $incEndCol . $this->layout['incidencias']['title']);
                $this->applyStyle($sheet, 'A' . $this->layout['incidencias']['title'] . ':' . $incEndCol . $this->layout['incidencias']['title'], $styles['section_title']);

                $this->applyStyle($sheet, 'A' . $this->layout['incidencias']['header'], $styles['table_header_left']);
                $this->applyStyle($sheet, 'B' . $this->layout['incidencias']['header'], $styles['table_header']);

                $usuarios = $this->reportData['usuarios'] ?? [];
                foreach ($usuarios as $index => $usuario) {
                    $colIndex = 3 + $index;
                    $col = $this->col($colIndex);
                    $color = $this->coloresResponsables[$index % count($this->coloresResponsables)];

                    $sheet->getStyle("{$col}{$this->layout['incidencias']['header']}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                            'name' => 'Segoe UI',
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'color' => ['rgb' => $color],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E2E8F0'],
                            ],
                        ],
                    ]);
                }

                foreach ($this->layout['incidencias']['dataRows'] as $item) {
                    $range = 'A' . $item['row'] . ':' . $incEndCol . $item['row'];

                    if ($item['type'] === 'padre') {
                        $this->applyStyle($sheet, $range, $styles['parent_row']);
                    } else {
                        $this->applyStyle($sheet, $range, $styles['child_row']);
                    }

                    $sheet->getStyle('B' . $item['row'] . ':' . $incEndCol . $item['row'])
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $this->applyStyle($sheet, 'A' . $this->layout['incidencias']['totalRow'] . ':' . $incEndCol . $this->layout['incidencias']['totalRow'], $styles['total_row']);
                $sheet->getStyle('B' . $this->layout['incidencias']['totalRow'] . ':' . $incEndCol . $this->layout['incidencias']['totalRow'])
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setAutoFilter(
                    'A' . $this->layout['incidencias']['header'] . ':' . $incEndCol . $this->layout['incidencias']['totalRow']
                );

                $sheet->mergeCells('A' . $this->layout['categorias']['title'] . ':E' . $this->layout['categorias']['title']);
                $this->applyStyle($sheet, 'A' . $this->layout['categorias']['title'] . ':E' . $this->layout['categorias']['title'], $styles['section_title']);
                $this->applyStyle($sheet, 'A' . $this->layout['categorias']['header'] . ':E' . $this->layout['categorias']['header'], $styles['table_header']);

                foreach ($this->layout['categorias']['dataRows'] as $index => $row) {
                    $styleKey = ($index % 2 === 0) ? 'normal' : 'normal_alt';
                    $centerKey = ($index % 2 === 0) ? 'center' : 'center_alt';
                    $this->applyStyle($sheet, "A{$row}:C{$row}", $styles[$styleKey]);
                    $this->applyStyle($sheet, "D{$row}:E{$row}", $styles[$centerKey]);
                }

                $this->applyStyle($sheet, 'A' . $this->layout['categorias']['totalRow'] . ':E' . $this->layout['categorias']['totalRow'], $styles['total_row']);
                $sheet->getStyle('D' . $this->layout['categorias']['totalRow'] . ':E' . $this->layout['categorias']['totalRow'])
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if (isset($this->layout['responsables'])) {
                    $sheet->mergeCells('A' . $this->layout['responsables']['title'] . ':F' . $this->layout['responsables']['title']);
                    $this->applyStyle($sheet, 'A' . $this->layout['responsables']['title'] . ':F' . $this->layout['responsables']['title'], $styles['section_title']);
                    $this->applyStyle($sheet, 'A' . $this->layout['responsables']['header'] . ':F' . $this->layout['responsables']['header'], $styles['table_header']);

                    foreach ($this->layout['responsables']['dataRows'] as $index => $row) {
                        $styleKey = ($index % 2 === 0) ? 'normal' : 'normal_alt';
                        $centerKey = ($index % 2 === 0) ? 'center' : 'center_alt';
                        $this->applyStyle($sheet, "A{$row}", $styles['label']);
                        $this->applyStyle($sheet, "B{$row}:E{$row}", $styles[$styleKey]);
                        $this->applyStyle($sheet, "F{$row}", $styles[$centerKey]);
                    }
                }

                $sheet->mergeCells('A' . $this->layout['tiempos']['actualTitle'] . ':C' . $this->layout['tiempos']['actualTitle']);
                $this->applyStyle($sheet, 'A' . $this->layout['tiempos']['actualTitle'] . ':C' . $this->layout['tiempos']['actualTitle'], $styles['section_title']);

                foreach ($this->layout['tiempos']['actualRows'] as $index => $row) {
                    if ($index === 3) {
                        $this->applyStyle($sheet, "A{$row}", $styles['green_row']);
                        $this->applyStyle($sheet, "B{$row}", $styles['green_center']);
                        $this->applyStyle($sheet, "C{$row}", $styles['note']);
                    } else {
                        $this->applyStyle($sheet, "A{$row}", $styles['label']);
                        $this->applyStyle($sheet, "B{$row}", $styles['center']);
                        $this->applyStyle($sheet, "C{$row}", $styles['note']);
                    }
                }

                $sheet->mergeCells('A' . $this->layout['tiempos']['prevTitle'] . ':C' . $this->layout['tiempos']['prevTitle']);
                $this->applyStyle($sheet, 'A' . $this->layout['tiempos']['prevTitle'] . ':C' . $this->layout['tiempos']['prevTitle'], $styles['section_title']);

                foreach ($this->layout['tiempos']['prevRows'] as $index => $row) {
                    if ($index === 3) {
                        $this->applyStyle($sheet, "A{$row}", $styles['green_row']);
                        $this->applyStyle($sheet, "B{$row}", $styles['green_center']);
                        $this->applyStyle($sheet, "C{$row}", $styles['note']);
                    } else {
                        $this->applyStyle($sheet, "A{$row}", $styles['label']);
                        $this->applyStyle($sheet, "B{$row}", $styles['center']);
                        $this->applyStyle($sheet, "C{$row}", $styles['note']);
                    }
                }

                $sheet->mergeCells('A' . $this->layout['tiempos']['noteRow'] . ':C' . $this->layout['tiempos']['noteRow']);
                $this->applyStyle($sheet, 'A' . $this->layout['tiempos']['noteRow'] . ':C' . $this->layout['tiempos']['noteRow'], $styles['note']);
                $sheet->getStyle('A' . $this->layout['tiempos']['noteRow'])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);

                $sheet->mergeCells('A' . $this->layout['comparativo']['title'] . ':D' . $this->layout['comparativo']['title']);
                $this->applyStyle($sheet, 'A' . $this->layout['comparativo']['title'] . ':D' . $this->layout['comparativo']['title'], $styles['section_title']);
                $this->applyStyle($sheet, 'A' . $this->layout['comparativo']['header'] . ':D' . $this->layout['comparativo']['header'], $styles['table_header']);

                foreach ($this->layout['comparativo']['dataRows'] as $index => $row) {
                    $styleKey = ($index % 2 === 0) ? 'normal' : 'normal_alt';
                    $centerKey = ($index % 2 === 0) ? 'center' : 'center_alt';
                    $this->applyStyle($sheet, "A{$row}", $styles['label']);
                    $this->applyStyle($sheet, "B{$row}:D{$row}", $styles[$centerKey]);
                }

                // ========== ESTILOS PARA TABLAS DE SOLICITUDES ==========
                if (isset($this->layout['solicitudes_gerencia'])) {
                    // Tabla de Gerencia
                    $sheet->mergeCells('A' . $this->layout['solicitudes_gerencia']['start'] . ':E' . $this->layout['solicitudes_gerencia']['start']);
                    $this->applyStyle($sheet, 'A' . $this->layout['solicitudes_gerencia']['start'] . ':E' . $this->layout['solicitudes_gerencia']['start'], $styles['section_title']);
                    
                    // Headers
                    $this->applyStyle($sheet, 'A' . $this->layout['solicitudes_gerencia']['headers'] . ':E' . $this->layout['solicitudes_gerencia']['headers'], $styles['table_header']);
                    
                    // Data rows
                    for ($row = $this->layout['solicitudes_gerencia']['dataStart']; $row < $this->layout['solicitudes_gerencia']['totalRow']; $row++) {
                        $index = $row - $this->layout['solicitudes_gerencia']['dataStart'];
                        $styleKey = ($index % 2 === 0) ? 'normal' : 'normal_alt';
                        $centerKey = ($index % 2 === 0) ? 'center' : 'center_alt';
                        $this->applyStyle($sheet, "A{$row}", $styles['label']);
                        $this->applyStyle($sheet, "B{$row}:E{$row}", $styles[$centerKey]);
                    }
                    
                    // Total row
                    $this->applyStyle($sheet, 'A' . $this->layout['solicitudes_gerencia']['totalRow'], $styles['green_row']);
                    $this->applyStyle($sheet, 'B' . $this->layout['solicitudes_gerencia']['totalRow'] . ':E' . $this->layout['solicitudes_gerencia']['totalRow'], $styles['green_center']);
                }

                if (isset($this->layout['solicitudes_motivo'])) {
                    // Tabla de Motivo
                    $sheet->mergeCells('A' . $this->layout['solicitudes_motivo']['start'] . ':E' . $this->layout['solicitudes_motivo']['start']);
                    $this->applyStyle($sheet, 'A' . $this->layout['solicitudes_motivo']['start'] . ':E' . $this->layout['solicitudes_motivo']['start'], $styles['section_title']);
                    
                    // Headers
                    $this->applyStyle($sheet, 'A' . $this->layout['solicitudes_motivo']['headers'] . ':E' . $this->layout['solicitudes_motivo']['headers'], $styles['table_header']);
                    
                    // Data rows
                    for ($row = $this->layout['solicitudes_motivo']['dataStart']; $row < $this->layout['solicitudes_motivo']['totalRow']; $row++) {
                        $index = $row - $this->layout['solicitudes_motivo']['dataStart'];
                        $styleKey = ($index % 2 === 0) ? 'normal' : 'normal_alt';
                        $centerKey = ($index % 2 === 0) ? 'center' : 'center_alt';
                        $this->applyStyle($sheet, "A{$row}", $styles['label']);
                        $this->applyStyle($sheet, "B{$row}:E{$row}", $styles[$centerKey]);
                    }
                    
                    // Total row
                    $this->applyStyle($sheet, 'A' . $this->layout['solicitudes_motivo']['totalRow'], $styles['green_row']);
                    $this->applyStyle($sheet, 'B' . $this->layout['solicitudes_motivo']['totalRow'] . ':E' . $this->layout['solicitudes_motivo']['totalRow'], $styles['green_center']);
                }

                if (isset($this->layout['solicitudes_comparacion'])) {
                    // Tabla de Comparación
                    $sheet->mergeCells('A' . $this->layout['solicitudes_comparacion']['start'] . ':E' . $this->layout['solicitudes_comparacion']['start']);
                    $this->applyStyle($sheet, 'A' . $this->layout['solicitudes_comparacion']['start'] . ':E' . $this->layout['solicitudes_comparacion']['start'], $styles['section_title']);
                    
                    // Headers
                    $this->applyStyle($sheet, 'A' . $this->layout['solicitudes_comparacion']['headers'] . ':E' . $this->layout['solicitudes_comparacion']['headers'], $styles['table_header']);
                    
                    // Data rows (Cotización y Configuración)
                    $datosRow1 = $this->layout['solicitudes_comparacion']['dataStart'];
                    $datosRow2 = $this->layout['solicitudes_comparacion']['dataStart'] + 1;
                    
                    $this->applyStyle($sheet, "A{$datosRow1}", $styles['label']);
                    $this->applyStyle($sheet, "B{$datosRow1}:E{$datosRow1}", $styles['center']);
                    
                    $this->applyStyle($sheet, "A{$datosRow2}", $styles['label']);
                    $this->applyStyle($sheet, "B{$datosRow2}:E{$datosRow2}", $styles['center_alt']);
                    
                    // Total row
                    $this->applyStyle($sheet, 'A' . $this->layout['solicitudes_comparacion']['totalRow'], $styles['green_row']);
                    $this->applyStyle($sheet, 'B' . $this->layout['solicitudes_comparacion']['totalRow'] . ':E' . $this->layout['solicitudes_comparacion']['totalRow'], $styles['green_center']);
                }
            },
        ];
    }
}
