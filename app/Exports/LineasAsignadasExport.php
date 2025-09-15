<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LineasAsignadasExport implements FromView, WithEvents, ShouldAutoSize
{
    protected $datos;
    protected $filtros;

    public function __construct($datos, $filtros = [])
    {
        $this->datos = $datos;
        $this->filtros = $filtros;
    }

    public function view(): View
    {
        return view('reportes_especificos.export_lineas_asignadas_excel', [
            'datos' => $this->datos,
            'filtros' => $this->filtros
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Obtener el rango de datos
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();
                
                // Estilizar el encabezado
                $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4F46E5'] // Color púrpura
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // Estilizar las celdas de datos
                if ($lastRow > 1) {
                    $sheet->getStyle('A2:' . $lastColumn . $lastRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => '000000']
                            ]
                        ],
                        'alignment' => [
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                        ]
                    ]);

                    // Alternar colores de filas
                    for ($row = 2; $row <= $lastRow; $row++) {
                        if ($row % 2 == 0) {
                            $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F8FAFC']
                                ]
                            ]);
                        }
                    }
                }

                // Ajustar ancho de columnas
                foreach (range('A', $lastColumn) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Establecer altura de fila para el encabezado
                $sheet->getRowDimension(1)->setRowHeight(25);
            }
        ];
    }
}
