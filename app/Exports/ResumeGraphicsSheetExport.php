<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ResumeGraphicsSheetExport implements FromArray, WithCharts, WithEvents, WithTitle
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
    protected $resumenSheet;

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

        // Crear instancia de ResumenSheetExport para acceder a datos y gráficas
        $this->resumenSheet = new ResumenSheetExport(
            $this->tickets,
            $this->resumen,
            $this->tiempoPorEmpleado,
            $this->tiempoPorCategoria,
            $this->mes,
            $this->anio,
            $this->catalogo,
            $this->solicitudes,
            $this->metricasSolicitudes
        );
    }

    public function title(): string
    {
        return 'Carátula de Gráficas';
    }

    public function array(): array
    {
        return [
            ['Carátula de Gráficas'],
            [''],
        ];
    }

    public function charts(): array
    {
        // Las gráficas se refieren a la hoja Resumen, no a esta hoja (Carátula de Gráficas)
        return $this->resumenSheet->getChartsForSheet('Resumen');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge título a lo ancho de las gráficas
                $sheet->mergeCells('A1:T1');

                // Estilo del título
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 18,
                        'color' => ['rgb' => 'FFFFFF'],
                        'name'  => 'Calibri',
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E3A5F'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color'       => ['rgb' => '2563EB'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(45);

                // Anchos de columna para acomodar las 6 gráficas en cuadrícula 2x3
                for ($col = 1; $col <= 20; $col++) {
                    $sheet->getColumnDimensionByColumn($col)->setWidth(8.5);
                }
            },
        ];
    }
}

