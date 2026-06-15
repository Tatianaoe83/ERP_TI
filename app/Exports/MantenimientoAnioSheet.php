<?php

namespace App\Exports;

use App\Models\Mantenimiento;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MantenimientoAnioSheet implements FromArray, WithTitle, WithEvents
{
    protected int $anio;

    // Row tracking (populated during array())
    protected int $titleRow     = 1;
    protected int $legendRow    = 2;
    protected int $headerRow    = 7;
    protected int $dataStartRow = 8;
    protected int $lastRow      = 8;

    // [rowNum => [monthNum(1-12) => colorHex]]
    protected array $cellStyles = [];
    // row numbers that are gerencia group headers
    protected array $gerenciaRows = [];
    // [['row' => int, 'color' => string], ...]
    protected array $legendItemRows = [];

    protected const MONTHS     = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    protected const TOTAL_COLS = 17; // A–Q
    protected const LAST_COL   = 'Q';

    // Hex colors (no #)
    protected const COLORS = [
        'realizado'    => '70AD47', // green
        'pendiente'    => '4472C4', // blue
        'reprogramado' => 'ED7D31', // orange
        'title_bg'     => '101D49',
        'header_bg'    => '1E3A8A',
        'gerencia_bg'  => 'DCE6F1',
        'legend_bg'    => 'F8FAFC',
    ];

    public function __construct(int $anio)
    {
        $this->anio = $anio;
    }

    public function title(): string
    {
        return (string) $this->anio;
    }

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
            ->orderBy('mantenimientos.FechaMantenimiento')
            ->get();

        $rows       = [];
        $currentRow = 1;

        // ── Row 1: Title ─────────────────────────────────────────────────────
        $titleRow    = array_fill(0, self::TOTAL_COLS, '');
        $titleRow[0] = "Programación de Mantenimientos — {$this->anio}";
        $rows[]      = $titleRow;
        $this->titleRow = $currentRow++;

        // ── Row 2: Legend title ──────────────────────────────────────────────
        $legendTitleRow    = array_fill(0, self::TOTAL_COLS, '');
        $legendTitleRow[0] = 'Leyenda:';
        $rows[]            = $legendTitleRow;
        $this->legendRow   = $currentRow++;

        // ── Rows 3–8: Legend items (col B, one per row) ───────────────────────
        $legendItems = [
            [self::COLORS['realizado'],    'Realizado'],
            [self::COLORS['pendiente'],    'Pendiente'],
            [self::COLORS['reprogramado'], 'Reprogramado'],
        ];
        foreach ($legendItems as [$color, $label]) {
            $legendRow    = array_fill(0, self::TOTAL_COLS, '');
            $legendRow[1] = $label; // col B
            $rows[]       = $legendRow;
            $this->legendItemRows[] = ['row' => $currentRow, 'color' => $color];
            $currentRow++;
        }

        // ── Row 9: Column headers ────────────────────────────────────────────
        $rows[] = array_merge(
            ['Gerencia', 'Empleado', 'Folio', 'Tipo', 'Estatus'],
            self::MONTHS
        );
        $this->headerRow = $currentRow++;
        $this->dataStartRow = $currentRow;

        // ── Rows 5+: Data grouped by gerencia ────────────────────────────────
        $grouped = $mantenimientos->groupBy(fn ($m) => $m->NombreGerencia ?: 'Sin gerencia');

        foreach ($grouped as $gerencia => $items) {
            $gerRow    = array_fill(0, self::TOTAL_COLS, '');
            $gerRow[0] = $gerencia;
            $rows[]    = $gerRow;
            $this->gerenciaRows[] = $currentRow;
            $currentRow++;

            foreach ($items as $mant) {
                [$dataRow, $rowStyles] = $this->buildDataRow($mant);
                $rows[]                         = $dataRow;
                $this->cellStyles[$currentRow] = $rowStyles;
                $currentRow++;
            }
        }

        $this->lastRow = $currentRow - 1;

        return $rows;
    }

    // Returns [rowArray, stylesArray]
    private function buildDataRow(Mantenimiento $mant): array
    {
        $row    = array_fill(0, self::TOTAL_COLS, '');
        $row[1] = $mant->NombreEmpleado ?: '—';
        $row[2] = $mant->Folio          ?: '—';
        $row[3] = $mant->TipoMantenimiento ?: '—';
        $row[4] = $mant->EstatusMantenimiento;

        $styles  = [];
        $estatus = $mant->EstatusMantenimiento;

        $fechaOriginal    = $mant->FechaMantenimiento  ? Carbon::parse($mant->FechaMantenimiento)  : null;
        $fechaReprogramada = $mant->FechaReprogramada  ? Carbon::parse($mant->FechaReprogramada) : null;

        if (!$fechaOriginal) {
            return [$row, $styles];
        }

        $mesOrig    = $fechaOriginal->month; // 1-12
        $colIdxOrig = $mesOrig + 4;          // 0-based array: month 1 → index 5 (col F)

        if ($fechaReprogramada) {
            // Only mark the reprogrammed date
            $mesRep           = $fechaReprogramada->month;
            $row[$mesRep + 4] = $fechaReprogramada->day;
            $styles[$mesRep]  = $estatus === 'Realizado'
                ? self::COLORS['realizado']
                : self::COLORS['reprogramado'];
        } else {
            $row[$colIdxOrig] = $fechaOriginal->day;

            if ($estatus === 'Realizado') {
                $styles[$mesOrig] = self::COLORS['realizado'];
            } else {
                $styles[$mesOrig] = self::COLORS['pendiente'];
            }
        }

        return [$row, $styles];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = self::LAST_COL;

                // ── Title ────────────────────────────────────────────────────
                $sheet->mergeCells("A{$this->titleRow}:{$lastCol}{$this->titleRow}");
                $sheet->getStyle("A{$this->titleRow}:{$lastCol}{$this->titleRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLORS['title_bg']]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($this->titleRow)->setRowHeight(38);

                // ── Legend title row ─────────────────────────────────────────
                $sheet->getStyle("A{$this->legendRow}:{$lastCol}{$this->legendRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLORS['legend_bg']]],
                    'font' => ['bold' => true, 'size' => 9],
                ]);
                $sheet->getRowDimension($this->legendRow)->setRowHeight(18);

                // ── Legend items (col B, one per row) ────────────────────────
                foreach ($this->legendItemRows as $item) {
                    $sheet->getStyle("A{$item['row']}:{$lastCol}{$item['row']}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLORS['legend_bg']]],
                        'font' => ['size' => 9],
                    ]);
                    $sheet->getStyle("B{$item['row']}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $item['color']]],
                        'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension($item['row'])->setRowHeight(16);
                }

                // ── Header row ───────────────────────────────────────────────
                $sheet->getStyle("A{$this->headerRow}:{$lastCol}{$this->headerRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLORS['header_bg']]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
                ]);
                $sheet->getRowDimension($this->headerRow)->setRowHeight(22);
                $sheet->freezePane("A{$this->dataStartRow}");

                // ── Gerencia group rows ───────────────────────────────────────
                foreach ($this->gerenciaRows as $gerRow) {
                    $sheet->mergeCells("A{$gerRow}:{$lastCol}{$gerRow}");
                    $sheet->getStyle("A{$gerRow}:{$lastCol}{$gerRow}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1E3A8A']],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLORS['gerencia_bg']]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '4472C4']]],
                    ]);
                    $sheet->getRowDimension($gerRow)->setRowHeight(20);
                }

                // ── Data rows: alternating bg + Gantt cell colors ─────────────
                $rowIdx = 0;
                foreach ($this->cellStyles as $rowNum => $monthStyles) {
                    $bgColor = ($rowIdx % 2 === 0) ? 'FFFFFF' : 'F5F8FF';
                    $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                        'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $rowIdx++;

                    // Color each marked month cell
                    foreach ($monthStyles as $month => $color) {
                        // PhpSpreadsheet 1-based: month 1 → col F = 6
                        $colLetter = Coordinate::stringFromColumnIndex($month + 5);
                        $cell      = "{$colLetter}{$rowNum}";

                        $fontColor = 'FFFFFF';

                        $sheet->getStyle($cell)->applyFromArray([
                            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                            'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => $fontColor]],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
                        ]);
                    }
                }

                // ── Column widths ─────────────────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(28); // Gerencia
                $sheet->getColumnDimension('B')->setWidth(28); // Empleado
                $sheet->getColumnDimension('C')->setWidth(12); // Folio
                $sheet->getColumnDimension('D')->setWidth(12); // Tipo
                $sheet->getColumnDimension('E')->setWidth(18);
                 $sheet->getColumnDimension('H')->setWidth(18); // Estatus

                // Month columns F–Q
                foreach (range(6, 17) as $colIndex) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setWidth(8);
                }
            },
        ];
    }
}
