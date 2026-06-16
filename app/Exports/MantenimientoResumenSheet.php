<?php

namespace App\Exports;

use App\Models\Mantenimiento;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Bloque izquierdo A–E: KPIs + tabla de gerencia
 * Bloque derecho  J–V: datos fuente de gráficas (quedan bajo el overlay del chart)
 *
 *   Row 3: KPI section header (A:E) | mes header K=Ene…V=Dic  ← bajo bar J3:W22
 *   Row 4: KPI headers      (A:E)  | "Programados" + vals K–V ← bajo bar
 *   Row 5: KPI valores      (A:E)  | "Realizados"  + vals K–V ← bajo bar
 *   Row 6–N: spacer + ger data
 *   Row 23: tipo[0] en J:K                                     ← bajo pie J23:W42
 *   Row 24: tipo[1] en J:K
 */
class MantenimientoResumenSheet implements FromArray, WithTitle, WithEvents, WithCharts
{
    protected int   $anio;
    protected array $layout  = [];
    protected int   $lastRow = 1;

    protected const MONTHS     = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    protected const TOTAL_COLS = 23;  // A–W  (índices 0-22)
    protected const LEFT_COL   = 'E'; // borde del bloque de datos
    protected const LAST_COL   = 'W';
    protected const J          = 9;   // índice 0-based de col J
    protected const K          = 10;  // índice 0-based de col K

    protected const C = [
        'title_bg'   => '101D49',
        'section_bg' => '1E3A8A',
        'kpi_blue'   => '4472C4',
        'kpi_green'  => '70AD47',
        'kpi_orange' => 'ED7D31',
        'kpi_red'    => 'E53E3E',
        'kpi_gray'   => '6B7280',
        'header_bg'  => '1E3A8A',
        'alt_row'    => 'F5F8FF',
        'good_bg'    => 'DCFCE7',
        'warn_bg'    => 'FEF9C3',
        'bad_bg'     => 'FEE2E2',
    ];

    public function __construct(int $anio)
    {
        $this->anio = $anio;
    }

    public function title(): string
    {
        return "Resumen {$this->anio}";
    }

    // ── Data ─────────────────────────────────────────────────────────────────

    public function array(): array
    {
        Carbon::setLocale('es');

        $mantenimientos = Mantenimiento::with([
            'empleado.puestos.departamentos.gerencia',
            'inventarioEquipo.empleados.puestos.departamentos.gerencia',
        ])
            ->where('mantenimientos.AnioProgramacion', $this->anio)
            ->leftJoin('inventarioequipo as ie', 'ie.InventarioID', '=', 'mantenimientos.InventarioID')
            ->leftJoin('empleados as e', 'e.EmpleadoID', '=', 'ie.EmpleadoID')
            ->leftJoin('puestos as p', 'p.PuestoID', '=', 'e.PuestoID')
            ->leftJoin('departamentos as d', 'd.DepartamentoID', '=', 'p.DepartamentoID')
            ->leftJoin('gerencia as g', 'g.GerenciaID', '=', 'd.GerenciaID')
            ->select('mantenimientos.*')
            ->orderBy('g.NombreGerencia')
            ->orderBy('e.NombreEmpleado')
            ->get();

        // ── Aggregates ───────────────────────────────────────────────────────
        $total         = $mantenimientos->count();
        $realizados    = $mantenimientos->where('EstatusMantenimiento', 'Realizado')->count();
        $pendientes    = $mantenimientos->where('EstatusMantenimiento', 'Pendiente')->count();
        $reprogramados = $mantenimientos->filter(fn ($m) => !empty($m->FechaReprogramada))->count();
        $cumplimiento  = $total > 0 ? round(($realizados / $total) * 100) : 0;

        // Tipos para pie chart (máx 2)
        $tiposColeccion = $mantenimientos
            ->groupBy(fn ($m) => $m->TipoMantenimiento ?: 'Sin tipo')
            ->map(fn ($g, $k) => ['tipo' => $k, 'total' => $g->count()])
            ->sortByDesc('total')
            ->values();
        $tipoCount = min($tiposColeccion->count(), 2);

        // Gerencia (mayor → menor cumplimiento)
        $porGerencia = $mantenimientos
            ->groupBy(fn ($m) => $m->NombreGerencia ?: 'Sin gerencia')
            ->map(fn ($g) => [
                'total'      => $g->count(),
                'realizados' => $g->where('EstatusMantenimiento', 'Realizado')->count(),
                'pendientes' => $g->where('EstatusMantenimiento', 'Pendiente')->count(),
            ])
            ->map(fn ($d) => array_merge($d, [
                'cumplimiento' => $d['total'] > 0 ? round(($d['realizados'] / $d['total']) * 100) : 0,
            ]))
            ->sortByDesc('cumplimiento')
            ->map(fn ($d, $k) => array_merge(['gerencia' => $k], $d))
            ->values();

        // Mes para bar chart
        $progData = [];
        $realData = [];
        for ($m = 1; $m <= 12; $m++) {
            $enMes      = $mantenimientos->filter(
                fn ($mant) => $mant->FechaMantenimiento &&
                    Carbon::parse($mant->FechaMantenimiento)->month === $m
            );
            $progData[] = $enMes->count();
            $realData[] = $enMes->where('EstatusMantenimiento', 'Realizado')->count();
        }

        // ── Build rows ───────────────────────────────────────────────────────
        $rows       = [];
        $E          = self::TOTAL_COLS;
        $currentRow = 1;

        // ── Row 1: Título ────────────────────────────────────────────────────
        $r    = array_fill(0, $E, '');
        $r[0] = "Resumen de Mantenimientos — {$this->anio}";
        $rows[] = $r;
        $this->layout['title'] = $currentRow++;

        // ── Row 2: Spacer ────────────────────────────────────────────────────
        $rows[] = array_fill(0, $E, '');
        $currentRow++;

        // ── Row 3: KPI section header | mes header K–V (bajo bar J3:W22) ────
        $r    = array_fill(0, $E, '');
        $r[0] = 'Indicadores generales';
        $r[self::J] = '';
        foreach (self::MONTHS as $i => $mes) { $r[self::K + $i] = $mes; }
        $rows[] = $r;
        $this->layout['kpi_section']    = $currentRow;
        $this->layout['mes_header_row'] = $currentRow++;

        // ── Row 4: KPI headers | mes prog K–V (bajo bar) ─────────────────────
        $r    = array_fill(0, $E, '');
        $r[0] = 'Total'; $r[1] = 'Realizados'; $r[2] = 'Pendientes';
        $r[3] = '% Cumplimiento'; $r[4] = 'Reprogramados';
        $r[self::J] = 'Programados';
        foreach ($progData as $i => $val) { $r[self::K + $i] = $val; }
        $rows[] = $r;
        $this->layout['kpi_header']   = $currentRow;
        $this->layout['mes_prog_row'] = $currentRow++;

        // ── Row 5: KPI valores | mes real K–V (bajo bar) ─────────────────────
        $r    = array_fill(0, $E, '');
        $r[0] = $total; $r[1] = $realizados; $r[2] = $pendientes;
        $r[3] = $cumplimiento . '%'; $r[4] = $reprogramados;
        $r[self::J] = 'Realizados';
        foreach ($realData as $i => $val) { $r[self::K + $i] = $val; }
        $rows[] = $r;
        $this->layout['kpi_values']   = ['row' => $currentRow, 'cumplimiento' => $cumplimiento];
        $this->layout['mes_real_row'] = $currentRow++;

        // ── Row 6: Spacer ─────────────────────────────────────────────────────
        $rows[] = array_fill(0, $E, '');
        $currentRow++;

        // ── Row 7: Gerencia section header ────────────────────────────────────
        $r    = array_fill(0, $E, '');
        $r[0] = 'Cumplimiento por gerencia (mayor a menor)';
        $rows[] = $r;
        $this->layout['ger_section'] = $currentRow++;

        // ── Row 8: Gerencia headers ───────────────────────────────────────────
        $r    = array_fill(0, $E, '');
        $r[0] = 'Gerencia'; $r[1] = 'Total'; $r[2] = 'Realizados';
        $r[3] = 'Pendientes'; $r[4] = '% Cumplimiento';
        $rows[] = $r;
        $this->layout['ger_header'] = $currentRow++;

        // ── Rows 9+: Gerencia data ────────────────────────────────────────────
        $this->layout['ger_rows'] = [];
        foreach ($porGerencia as $idx => $data) {
            $r    = array_fill(0, $E, '');
            $r[0] = $data['gerencia'];
            $r[1] = $data['total'];
            $r[2] = $data['realizados'];
            $r[3] = $data['pendientes'];
            $r[4] = $data['cumplimiento'] . '%';
            $rows[] = $r;
            $this->layout['ger_rows'][] = [
                'row'         => $currentRow,
                'cumplimiento' => $data['cumplimiento'],
                'alt'         => $idx % 2 !== 0,
            ];
            $currentRow++;
        }

        // ── Asegurar que existan al menos hasta row 24 para tipo data ─────────
        while ($currentRow <= 24) {
            $rows[] = array_fill(0, $E, '');
            $currentRow++;
        }

        $this->lastRow = $currentRow - 1;

        // ── Tipo data fija en rows 23–24 (bajo pie J23:W42) ──────────────────
        // Los índices 0-based del array: row 23 → $rows[22], row 24 → $rows[23]
        $this->layout['tipo_first_data'] = 23;
        $this->layout['tipo_last_data']  = 22 + max(1, $tipoCount);
        for ($i = 0; $i < $tipoCount; $i++) {
            $rows[22 + $i][self::J] = $tiposColeccion[$i]['tipo'];
            $rows[22 + $i][self::K] = $tiposColeccion[$i]['total'];
        }

        return $rows;
    }

    // ── Charts ────────────────────────────────────────────────────────────────

    public function charts(): array
    {
        $sn = $this->title();

        $mesH   = $this->layout['mes_header_row']  ?? 3;
        $mesPr  = $this->layout['mes_prog_row']    ?? 4;
        $mesRe  = $this->layout['mes_real_row']    ?? 5;

        $tipoFirst = $this->layout['tipo_first_data'] ?? 23;
        $tipoLast  = $this->layout['tipo_last_data']  ?? 24;
        $tipoCount = max(1, $tipoLast - $tipoFirst + 1);

        // ── Bar: distribución mensual  J3:W22 ────────────────────────────────
        // Datos: K(mesH):V(mesH) = Ene–Dic
        $barCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING,
                "'{$sn}'!\$K\${$mesH}:\$V\${$mesH}", null, 12),
        ];
        $barLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING,
                "'{$sn}'!\$J\${$mesPr}", null, 1),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING,
                "'{$sn}'!\$J\${$mesRe}", null, 1),
        ];
        $barValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER,
                "'{$sn}'!\$K\${$mesPr}:\$V\${$mesPr}", null, 12),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER,
                "'{$sn}'!\$K\${$mesRe}:\$V\${$mesRe}", null, 12),
        ];

        $barSeries = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            [0, 1],
            $barLabels,
            $barCategories,
            $barValues
        );
        $barSeries->setPlotDirection(DataSeries::DIRECTION_COL);

        $bar = new Chart(
            'mes_bar',
            new Title('Distribución Mensual de Mantenimientos'),
            new Legend(Legend::POSITION_BOTTOM),
            new PlotArea(null, [$barSeries])
        );
        $bar->setTopLeftCell('J3');
        $bar->setBottomRightCell('W22');

        // ── Pie: distribución por tipo  J23:W42 ──────────────────────────────
        // Datos: J(23):J(24) categorías, K(23):K(24) valores
        $pieCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING,
                "'{$sn}'!\$J\${$tipoFirst}:\$J\${$tipoLast}", null, $tipoCount),
        ];
        $pieValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER,
                "'{$sn}'!\$K\${$tipoFirst}:\$K\${$tipoLast}", null, $tipoCount),
        ];

        $pieSeries = new DataSeries(
            DataSeries::TYPE_PIECHART,
            null,
            [0],
            [],
            $pieCategories,
            $pieValues
        );

        $pieLayout = new Layout();
        $pieLayout->setShowVal(true);
        $pieLayout->setShowPercent(true);
        $pieLayout->setShowCatName(true);

        $pie = new Chart(
            'tipo_pie',
            new Title('Distribución por Tipo de Mantenimiento'),
            new Legend(Legend::POSITION_BOTTOM),
            new PlotArea($pieLayout, [$pieSeries])
        );
        $pie->setTopLeftCell('J23');
        $pie->setBottomRightCell('W42');

        return [$bar, $pie];
    }

    // ── Styles ────────────────────────────────────────────────────────────────

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $cumplColor = fn (int $pct) => $pct >= 80
                    ? self::C['good_bg']
                    : ($pct >= 50 ? self::C['warn_bg'] : self::C['bad_bg']);

                $sectionTitle = function (int $row) use ($sheet) {
                    $sheet->mergeCells("A{$row}:" . self::LEFT_COL . "{$row}");
                    $sheet->getStyle("A{$row}:" . self::LEFT_COL . "{$row}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C['section_bg']]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(22);
                };

                $tableHeader = function (int $row) use ($sheet) {
                    $sheet->getStyle("A{$row}:" . self::LEFT_COL . "{$row}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C['header_bg']]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(20);
                };

                // Título
                $r = $this->layout['title'];
                $sheet->mergeCells("A{$r}:" . self::LAST_COL . "{$r}");
                $sheet->getStyle("A{$r}:" . self::LAST_COL . "{$r}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::C['title_bg']]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($r)->setRowHeight(38);

                // KPI section
                $sectionTitle($this->layout['kpi_section']);
                $tableHeader($this->layout['kpi_header']);

                $kpiRow   = $this->layout['kpi_values']['row'];
                $cumplPct = $this->layout['kpi_values']['cumplimiento'];
                $kpiColors = [
                    'A' => self::C['kpi_blue'],
                    'B' => self::C['kpi_green'],
                    'C' => self::C['kpi_orange'],
                    'D' => $cumplPct >= 80 ? self::C['kpi_green'] : ($cumplPct >= 50 ? self::C['kpi_orange'] : self::C['kpi_red']),
                    'E' => self::C['kpi_gray'],
                ];
                foreach ($kpiColors as $col => $color) {
                    $sheet->getStyle("{$col}{$kpiRow}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                        'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }
                $sheet->getRowDimension($kpiRow)->setRowHeight(32);

                // Gerencia section
                $sectionTitle($this->layout['ger_section']);
                $tableHeader($this->layout['ger_header']);

                foreach ($this->layout['ger_rows'] as $info) {
                    $r  = $info['row'];
                    $bg = $info['alt'] ? self::C['alt_row'] : 'FFFFFF';
                    $sheet->getStyle("A{$r}:" . self::LEFT_COL . "{$r}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                        'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("E{$r}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $cumplColor($info['cumplimiento'])]],
                        'font'      => ['bold' => true],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }

                // Anchos
                $sheet->getColumnDimension('A')->setWidth(30);
                $sheet->getColumnDimension('B')->setWidth(9);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(12);
                $sheet->getColumnDimension('E')->setWidth(16);
                // F–I espacio entre bloque izq. y charts
                foreach (range(6, 9) as $ci) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($ci))->setWidth(2);
                }
                // J–W: zona de charts
                foreach (range(10, 23) as $ci) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($ci))->setWidth(7);
                }
            },
        ];
    }
}
