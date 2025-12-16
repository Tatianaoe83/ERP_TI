<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;

class ResumenSheetExport implements FromView, WithEvents, WithTitle, ShouldAutoSize
{
    protected $resumen;
    protected $mes;
    protected $anio;

    public function __construct($resumen, $mes, $anio)
    {
        $this->resumen = $resumen;
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function view(): View
    {
        return view('tickets.export.resumen-excel', [
            'resumen' => $this->resumen,
            'mes' => $this->mes,
            'anio' => $this->anio
        ]);
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Autoajustar columnas
                foreach (range('A', 'F') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Estilo para encabezado principal (fila 1)
                $headerMainStyle = [
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E3A8A']
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ]
                ];
                $sheet->getStyle('A1:F1')->applyFromArray($headerMainStyle);
                $sheet->mergeCells('A1:F1');
                $sheet->getRowDimension(1)->setRowHeight(30);
                
                // Estilo para información general (fila 2)
                $sheet->getStyle('A2:F2')->applyFromArray([
                    'alignment' => [
                        'wrapText' => true,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ]
                ]);
                $sheet->getRowDimension(2)->setRowHeight(25);
                
                // Estilo para encabezados de tabla (fila 4)
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4']
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '1E40AF']
                        ]
                    ]
                ];
                
                // Estilo para encabezados de tabla (fila 4 - después del espacio)
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4']
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '1E40AF']
                        ]
                    ]
                ];
                
                // Buscar fila de encabezado de tabla (fila 4)
                $highestRow = $sheet->getHighestRow();
                if ($sheet->getCell('A4')->getValue() && stripos($sheet->getCell('A4')->getValue(), 'Gerencia') !== false) {
                    $sheet->getStyle('A4:F4')->applyFromArray($headerStyle);
                    $sheet->getRowDimension(4)->setRowHeight(25);
                    
                    // Aplicar estilos a filas de datos
                    for ($dataRow = 5; $dataRow <= $highestRow; $dataRow++) {
                        $nextCell = $sheet->getCell('A' . $dataRow)->getValue();
                        // Si encontramos otro encabezado o título, parar
                        if (is_string($nextCell) && (stripos($nextCell, 'TOTALES POR EMPLEADO') !== false || ($sheet->getCell('A' . $dataRow)->getValue() && stripos($sheet->getCell('A' . $dataRow)->getValue(), 'Empleado') !== false && $dataRow > 4))) {
                            break;
                        }
                        
                        // Solo aplicar si la fila tiene datos
                        if ($sheet->getCell('A' . $dataRow)->getValue() !== null && $sheet->getCell('A' . $dataRow)->getValue() !== '') {
                            $sheet->getStyle('A' . $dataRow . ':F' . $dataRow)->applyFromArray([
                                'borders' => [
                                    'allBorders' => [
                                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                        'color' => ['rgb' => 'E5E7EB']
                                    ]
                                ],
                                'alignment' => [
                                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                                    'wrapText' => true
                                ]
                            ]);
                            
                            if (($dataRow - 4) % 2 == 0) {
                                $sheet->getStyle('A' . $dataRow . ':F' . $dataRow)->applyFromArray([
                                    'fill' => [
                                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                        'startColor' => ['rgb' => 'F9FAFB']
                                    ]
                                ]);
                            }
                        }
                    }
                }
                
                // Buscar y estilizar tabla de empleados
                for ($row = 1; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell('A' . $row)->getValue();
                    if (is_string($cellValue) && stripos($cellValue, 'TOTALES POR EMPLEADO') !== false) {
                        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($headerMainStyle);
                        $sheet->mergeCells('A' . $row . ':F' . $row);
                        $sheet->getRowDimension($row)->setRowHeight(30);
                        
                        // Estilizar encabezado de tabla de empleados (siguiente fila)
                        $nextRowValue = $sheet->getCell('A' . ($row + 1))->getValue();
                        if ($nextRowValue && stripos($nextRowValue, 'Empleado') !== false) {
                            $sheet->getStyle('A' . ($row + 1) . ':F' . ($row + 1))->applyFromArray($headerStyle);
                            $sheet->getRowDimension($row + 1)->setRowHeight(25);
                            
                            // Aplicar estilos a filas de empleados
                            for ($dataRow = $row + 2; $dataRow <= $highestRow; $dataRow++) {
                                // Solo aplicar si la fila tiene datos
                                if ($sheet->getCell('A' . $dataRow)->getValue() !== null && $sheet->getCell('A' . $dataRow)->getValue() !== '') {
                                    $sheet->getStyle('A' . $dataRow . ':F' . $dataRow)->applyFromArray([
                                        'borders' => [
                                            'allBorders' => [
                                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                                'color' => ['rgb' => 'E5E7EB']
                                            ]
                                        ]
                                    ]);
                                    
                                    if (($dataRow - $row) % 2 == 0) {
                                        $sheet->getStyle('A' . $dataRow . ':F' . $dataRow)->applyFromArray([
                                            'fill' => [
                                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                                'startColor' => ['rgb' => 'F9FAFB']
                                            ]
                                        ]);
                                    }
                                }
                            }
                        }
                        break;
                    }
                }
            }
        ];
    }
}

