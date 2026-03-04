<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
    protected $tertipoAPadres = [];

    public int $cantidadUsuarios      = 0;
    public int $cantidadFilasGerencia = 0;
    public array $totalesPorTipo      = [];
    public int $ticketsSinClasificar  = 0;
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

        // 2. Construir catálogo desde jerarquía MAESTRA (tipotickets -> subtipo -> tertipo)
        // Así cada Tertipo aparece solo bajo su Subtipo correcto y no se mezclan categorías
        $this->catalogo = [];
        $this->tertipoAPadres = []; // Mapa Tertipo => [tipo, subtipo] para reparar tickets con NULL
        $ticketsSinClasificar = 0;

        try {
            $filasCatalogo = DB::select("
                SELECT DISTINCT tt.NombreTipo, st.NombreSubtipo, ter.NombreTertipo
                FROM tipotickets tt
                INNER JOIN subtipo st ON tt.SubtipoID = st.SubtipoID
                INNER JOIN tertipo ter ON st.TertipoID = ter.TertipoID
                WHERE tt.deleted_at IS NULL AND st.deleted_at IS NULL AND ter.deleted_at IS NULL
                ORDER BY tt.NombreTipo, st.NombreSubtipo, ter.NombreTertipo
            ");
            foreach ($filasCatalogo as $fila) {
                $tipo    = (string) ($fila->NombreTipo ?? '');
                $subtipo = (string) ($fila->NombreSubtipo ?? '');
                $tertipo = (string) ($fila->NombreTertipo ?? '');
                if ($tipo === '' || $subtipo === '' || $tertipo === '') continue;
                if (!isset($this->catalogo[$tipo])) $this->catalogo[$tipo] = [];
                if (!isset($this->catalogo[$tipo][$subtipo])) $this->catalogo[$tipo][$subtipo] = [];
                if (!in_array($tertipo, $this->catalogo[$tipo][$subtipo])) {
                    $this->catalogo[$tipo][$subtipo][] = $tertipo;
                }
                $this->tertipoAPadres[$tertipo] = [$tipo, $subtipo];
            }
        } catch (\Throwable $e) {
            $this->tertipoAPadres = [];
        }

        $fechaInicioReporte = Carbon::create($anioTarget, $mesTarget, 1)->subMonth()->startOfMonth();
        $fechaFinReporte    = Carbon::create($anioTarget, $mesTarget, 1)->endOfMonth();

        if (empty($this->catalogo)) {
            try {
                $filas = DB::select("
                    SELECT DISTINCT
                        COALESCE(tt.NombreTipo, 'Sin tipo') AS NombreTipo,
                        COALESCE(st.NombreSubtipo, 'Sin subtipo') AS NombreSubtipo,
                        COALESCE(ter.NombreTertipo, 'Sin incidencia') AS NombreTertipo
                    FROM tickets t
                    LEFT JOIN tipotickets tt ON t.TipoID = tt.TipoID
                    LEFT JOIN subtipo st ON t.SubtipoID = st.SubtipoID
                    LEFT JOIN tertipo ter ON t.TertipoID = ter.TertipoID
                    WHERE t.deleted_at IS NULL AND t.created_at >= ? AND t.created_at <= ?
                    ORDER BY NombreTipo, NombreSubtipo, NombreTertipo
                ", [$fechaInicioReporte, $fechaFinReporte]);
                foreach ($filas as $f) {
                    $t = (string) ($f->NombreTipo ?? 'Sin tipo');
                    $s = (string) ($f->NombreSubtipo ?? 'Sin subtipo');
                    $r = (string) ($f->NombreTertipo ?? 'Sin incidencia');
                    if (!isset($this->catalogo[$t])) $this->catalogo[$t] = [];
                    if (!isset($this->catalogo[$t][$s])) $this->catalogo[$t][$s] = [];
                    if (!in_array($r, $this->catalogo[$t][$s])) $this->catalogo[$t][$s][] = $r;
                }
            } catch (\Throwable $e) {}
        }

        // Añadir combinaciones de tickets que falten (Sin tipo, Sin subtipo, Sin incidencia)
        foreach ($tickets as $ticket) {
            $tipo    = (string) (optional($ticket->tipoticket)->NombreTipo ?: '');
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
            if (!in_array($tertipo, $this->catalogo[$tipo][$subtipo])) {
                $this->catalogo[$tipo][$subtipo][] = $tertipo;
            }
        }

        // Ordenar: poner "Sin subtipo" y "Sin incidencia" al final para que la jerarquía real quede primero
        $sinValores = ['Sin tipo', 'Sin subtipo', 'Sin incidencia'];
        uksort($this->catalogo, fn($a, $b) => in_array($a, $sinValores) && !in_array($b, $sinValores) ? 1 : (!in_array($a, $sinValores) && in_array($b, $sinValores) ? -1 : strcasecmp($a, $b)));
        foreach ($this->catalogo as $tipo => $subtipos) {
            uksort($subtipos, fn($a, $b) => ($a === 'Sin subtipo' && $b !== 'Sin subtipo') ? 1 : (($a !== 'Sin subtipo' && $b === 'Sin subtipo') ? -1 : strcasecmp($a, $b)));
            $this->catalogo[$tipo] = $subtipos;
            foreach ($subtipos as $subtipo => $ters) {
                usort($ters, fn($a, $b) => ($a === 'Sin incidencia' && $b !== 'Sin incidencia') ? 1 : (($a !== 'Sin incidencia' && $b === 'Sin incidencia') ? -1 : strcasecmp($a, $b)));
                $this->catalogo[$tipo][$subtipo] = $ters;
            }
        }

        // 3. Inicializar tabla agrupada en 0 (3 niveles: Tipo -> Subtipo -> Tertipo)
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

        // 4. Llenar datos
        foreach ($tickets as $ticket) {
            $ticketDate   = Carbon::parse($ticket->created_at);
            $ticketMes    = $ticketDate->month;
            $ticketAnio   = $ticketDate->year;
            $usuario      = (string) (optional($ticket->responsableTI)->NombreEmpleado ?? 'Sin Responsable');
            
            $tipo    = (string) (optional($ticket->tipoticket)->NombreTipo ?: 'Sin tipo');
            $subtipo = (string) (optional($ticket->subtipo)->NombreSubtipo ?: 'Sin subtipo');
            $tertipo = (string) (optional($ticket->tertipo)->NombreTertipo ?: 'Sin incidencia');

            // Corregir: si ticket tiene "Sin subtipo" pero el Tertipo existe en el maestro, usar el Subtipo correcto
            if ($subtipo === 'Sin subtipo' && $tertipo !== 'Sin incidencia' && isset($this->tertipoAPadres[$tertipo])) {
                [$tipo, $subtipo] = $this->tertipoAPadres[$tertipo];
            }
            $categoria = !empty($tertipo) && $tertipo !== 'Sin incidencia' ? $tertipo : ($subtipo !== 'Sin subtipo' ? $subtipo : $tipo);

            // SI ES EL MES ACTUAL
            if ($ticketMes === $mesTarget && $ticketAnio === $anioTarget) {
                $totalTicketsMesActual++;
                $tablaMesesUsuarios[$usuario][$mesActualCorto]++;

                if (!empty($tipo) && isset($tablaAgrupada[$tipo])) {
                    $tablaAgrupada[$tipo]['total_principal'][$usuario]++;
                    if (isset($tablaAgrupada[$tipo]['subtipos'][$subtipo])) {
                        $tablaAgrupada[$tipo]['subtipos'][$subtipo]['total_principal'][$usuario]++;
                        if (isset($tablaAgrupada[$tipo]['subtipos'][$subtipo]['tertipos'][$tertipo])) {
                            $tablaAgrupada[$tipo]['subtipos'][$subtipo]['tertipos'][$tertipo][$usuario]++;
                        }
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

        // 5. Tabla Resumen por Responsable con Tipo/Subtipo/Tertipo (vista alternativa con más valor)
        $tablaResponsableDetalle = [];
        foreach ($tickets as $ticket) {
            $ticketDate = Carbon::parse($ticket->created_at);
            if ($ticketDate->month !== $mesTarget || $ticketDate->year !== $anioTarget) continue;

            $resp = (string) (optional($ticket->responsableTI)->NombreEmpleado ?? 'Sin Responsable');
            $tipo = (string) (optional($ticket->tipoticket)->NombreTipo ?: 'Sin tipo');
            $sub = (string) (optional($ticket->subtipo)->NombreSubtipo ?: 'Sin subtipo');
            $ter = (string) (optional($ticket->tertipo)->NombreTertipo ?: 'Sin incidencia');

            $clave = "{$resp}|{$tipo}|{$sub}|{$ter}";
            if (!isset($tablaResponsableDetalle[$clave])) {
                $tablaResponsableDetalle[$clave] = ['responsable' => $resp, 'tipo' => $tipo, 'subtipo' => $sub, 'tertipo' => $ter, 'total' => 0, 'segundos' => []];
            }
            $tablaResponsableDetalle[$clave]['total']++;
            if (!empty($ticket->FechaFinProgreso) && $ticket->Estatus === 'Cerrado') {
                try {
                    $tablaResponsableDetalle[$clave]['segundos'][] = $ticketDate->diffInSeconds(Carbon::parse($ticket->FechaFinProgreso));
                } catch (\Exception $e) {}
            }
        }
        foreach ($tablaResponsableDetalle as &$r) {
            $r['tiempo_prom'] = count($r['segundos']) > 0 ? $this->formatSecondsToDays(array_sum($r['segundos']) / count($r['segundos'])) : '—';
        }
        uasort($tablaResponsableDetalle, fn($a, $b) => strcmp($a['responsable'], $b['responsable']) ?: (strcmp($a['tipo'], $b['tipo']) ?: (strcmp($a['subtipo'], $b['subtipo']) ?: strcmp($a['tertipo'], $b['tertipo']))));

        // 6. Tabla Incidencias detallada: Tipo | Subtipo | Tertipo | Cuenta | Tiempo Prom
        $tablaCategoriaDetallada = [];
        foreach ($tickets as $ticket) {
            $ticketDate = Carbon::parse($ticket->created_at);
            if ($ticketDate->month !== $mesTarget || $ticketDate->year !== $anioTarget) continue;

            $tipo = (string) (optional($ticket->tipoticket)->NombreTipo ?: 'Sin tipo');
            $sub  = (string) (optional($ticket->subtipo)->NombreSubtipo ?: 'Sin subtipo');
            $ter  = (string) (optional($ticket->tertipo)->NombreTertipo ?: 'Sin incidencia');
            $clave = "{$tipo}|{$sub}|{$ter}";

            if (!isset($tablaCategoriaDetallada[$clave])) {
                $tablaCategoriaDetallada[$clave] = ['tipo' => $tipo, 'subtipo' => $sub, 'tertipo' => $ter, 'total' => 0, 'segundos' => []];
            }
            $tablaCategoriaDetallada[$clave]['total']++;
            if (!empty($ticket->FechaFinProgreso) && $ticket->Estatus === 'Cerrado') {
                try {
                    $tablaCategoriaDetallada[$clave]['segundos'][] = $ticketDate->diffInSeconds(Carbon::parse($ticket->FechaFinProgreso));
                } catch (\Exception $e) {}
            }
        }
        foreach ($tablaCategoriaDetallada as &$v) {
            $v['tiempo_prom'] = count($v['segundos']) > 0 ? $this->formatSecondsToDays(array_sum($v['segundos']) / count($v['segundos'])) : '—';
        }
        uasort($tablaCategoriaDetallada, fn($a, $b) => strcmp($a['tipo'], $b['tipo']) ?: (strcmp($a['subtipo'], $b['subtipo']) ?: strcmp($a['tertipo'], $b['tertipo'])));

        // Solo 2 niveles: Tipo + Subtipo (sin Tertipo)
        $filasJerarquia = 0;
        foreach ($tablaAgrupada as $tipo => $datos) {
            $filasJerarquia++; // Tipo
            if (isset($datos['subtipos'])) {
                foreach ($datos['subtipos'] as $subtipo => $datosSub) {
                    $filasJerarquia++; // Subtipo
                }
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

        // Totales por Tipo (para gráfica resumida)
        $totalesPorTipo = [];
        foreach ($tablaAgrupada as $tipo => $datos) {
            $totalesPorTipo[$tipo] = array_sum($datos['total_principal'] ?? []);
        }
        arsort($totalesPorTipo);
        $this->totalesPorTipo      = $totalesPorTipo;
        $this->ticketsSinClasificar = $ticketsSinClasificar;

        return view('tickets.export.resumen-excel', [
            'tablaAgrupada'           => $tablaAgrupada,
            'tablaResponsableDetalle' => array_values($tablaResponsableDetalle),
            'tablaCategoriaDetallada' => array_values($tablaCategoriaDetallada),
            'totalesPorTipo'          => $totalesPorTipo,
            'ticketsSinClasificar'    => $ticketsSinClasificar,
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

                // Misma lógica que en charts(): filas antes de la tabla Incidencias
                $filasResumenPorTipo = !empty($this->totalesPorTipo) ? (2 + count($this->totalesPorTipo)) : 0;
                $filasAvisoSinClasificar = $this->ticketsSinClasificar > 0 ? 1 : 0;
                $filasAntesTabla = 4 + $filasResumenPorTipo + $filasAvisoSinClasificar + 2;
                $filaHeaderIncidencias = $filasAntesTabla;
                $currentRow = $filaHeaderIncidencias;

                $colGerEnd = $uCount + 2;
                $colLetraFin = $this->col($colGerEnd);

                foreach ($this->catalogo as $tipo => $subtipos) {
                    $filaTipo = ++$currentRow;

                    $sheet->getStyle("A{$filaTipo}:{$colLetraFin}{$filaTipo}")->getFont()->setSize(14)->setBold(true);

                    if (is_array($subtipos) && count($subtipos) > 0) {
                        foreach ($subtipos as $subtipo => $tertipos) {
                            $filaSubtipo = ++$currentRow;

                            $sheet->getStyle("A{$filaSubtipo}:{$colLetraFin}{$filaSubtipo}")->getFont()->setSize(12)->setBold(true);
                        }
                    }
                }

                $rangeGer = "A{$filaHeaderIncidencias}:" . $this->col($colGerEnd) . $currentRow;

                try {
                    $tableGer = new Table($rangeGer);
                    $tableGer->setName('TablaTiposTicket');
                    $tableGer->setShowTotalsRow(false);
                    $sheet->addTable($tableGer);
                } catch (\Exception $e) {
                    $sheet->setAutoFilter($rangeGer);
                }

                $sheet->freezePane('A' . ($filaHeaderIncidencias + 2));
            },
        ];
    }

    public function charts()
    {
        if ($this->cantidadFilasGerencia === 0 || $this->cantidadUsuarios === 0) {
            return [];
        }

       

        // Filas ANTES de la tabla Incidencias: KPI (4) + Resumen por Tipo (2 + N filas) + posible aviso sin clasificar (1) + título Incidencias (1) + encabezados (1)
        $filasResumenPorTipo = !empty($this->totalesPorTipo) ? (2 + count($this->totalesPorTipo)) : 0; // título + header + datos
        $filasAvisoSinClasificar = $this->ticketsSinClasificar > 0 ? 1 : 0;
        $filasAntesTabla = 4 + $filasResumenPorTipo + $filasAvisoSinClasificar + 2; // +2 = título y encabezados de Incidencias
        $filaEncabezados = $filasAntesTabla;      // Etiquetas de fila | Total general | usuarios
        $filaInicioDatos = $filasAntesTabla + 2;  // Primera fila de datos (Tipo, Subtipo, Tertipo)

        //print_r ($filasAntesTabla);
        //dd ($this->cantidadFilasGerencia);

        $filaFinDatos    = $filasAntesTabla + $this->cantidadFilasGerencia + 1; // Última fila de datos, ANTES de "Total general"

        $ejeY = [
            new DataSeriesValues('String', "Resumen!\$A\${$filaInicioDatos}:\$A\${$filaFinDatos}", null, $this->cantidadFilasGerencia),
        ];

        $seriesNombres = [];
        $valores       = [];

        for ($u = 0; $u < $this->cantidadUsuarios; $u++) {
            $colLetra        = $this->col($u + 3); // Col A=Etiquetas, B=Total, C+=usuarios
            $seriesNombres[] = new DataSeriesValues('String', "Resumen!\${$colLetra}\${$filaEncabezados}", null, 1);
            $valores[]       = new DataSeriesValues('Number', "Resumen!\${$colLetra}\${$filaInicioDatos}:\${$colLetra}\${$filaFinDatos}", null, $this->cantidadFilasGerencia);
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
        $title    = new Title('Incidencias por Tipo y Subtipo por Responsable');

        $chart = new Chart('grafica_gerencias', $title, $legend, $plotArea);

        // Gráfica más grande: desde encabezado hasta final de datos (ANTES de "Total general")
        $filaInicioGrafica = $filaEncabezados;
        $alturaChart       = max($this->cantidadFilasGerencia + 6, 28);
        $filaFinGrafica    = $filaInicioGrafica + $alturaChart;
        $colInicioChart    = $this->cantidadUsuarios + 4; // Columna a la derecha de la tabla (2 + usuarios + 1 espacio)
        $colFinChart       = $colInicioChart + 14;       // Gráfica más ancha (14 columnas)
        $colLetraInicio    = $this->col($colInicioChart);
        $colLetraFin       = $this->col($colFinChart);

        $chart->setTopLeftPosition("{$colLetraInicio}{$filaInicioGrafica}");
        $chart->setBottomRightPosition("{$colLetraFin}{$filaFinGrafica}");

        $charts = [$chart];

        // Segunda gráfica: Tickets por Tipo (resumen, vista más legible)
        if (count($this->totalesPorTipo) > 0) {
            // KPI=4 filas, Resumen por Tipo: fila 5=título, 6=header, 7+=datos
            $filaResumenDatos  = 7;
            $cantTipos         = count($this->totalesPorTipo);
            $filaResumenFin    = 6 + $cantTipos;

            $ejeY2 = [new DataSeriesValues('String', "Resumen!\$A\${$filaResumenDatos}:\$A\${$filaResumenFin}", null, $cantTipos)];
            $valores2 = [new DataSeriesValues('Number', "Resumen!\$B\${$filaResumenDatos}:\$B\${$filaResumenFin}", null, $cantTipos)];
            $labelSerie2 = [new DataSeriesValues('String', null, null, 1, ['Tickets por Tipo'])];
            $series2 = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_STANDARD,
                [0],
                $labelSerie2,
                $ejeY2,
                $valores2
            );
            $series2->setPlotDirection(DataSeries::DIRECTION_BAR);
            $plotArea2 = new PlotArea(null, [$series2]);
            $chart2 = new Chart('grafica_por_tipo', new Title('Tickets por Tipo'), new Legend(Legend::POSITION_BOTTOM, null, false), $plotArea2);
            $chart2->setTopLeftPosition("{$colLetraInicio}" . ($filaFinGrafica + 2));
            $chart2->setBottomRightPosition("{$colLetraFin}" . ($filaFinGrafica + 18));
            $charts[] = $chart2;
        }

        return $charts;
    }
}