<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Color;

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
                
                // Configurar anchos de columna optimizados
                $columnWidths = [
                    'A' => 30,  // Gerencia
                    'B' => 18,  // Total Incidencias
                    'C' => 15,  // Resueltos
                    'D' => 18,  // En Progreso
                    'E' => 15,  // Pendientes
                    'F' => 50,  // Responsable de Resolución (más ancha para mejor visualización)
                ];
                
                foreach (range('A', 'F') as $col) {
                    if (isset($columnWidths[$col])) {
                        $sheet->getColumnDimension($col)->setWidth($columnWidths[$col]);
                    } else {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }
                }
                
                // Estilo para encabezado principal (fila 1)
                $headerMainStyle = [
                    'font' => [
                        'bold' => true,
                        'size' => 18,
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
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                            'color' => ['rgb' => '1E40AF']
                        ]
                    ]
                ];
                $sheet->getStyle('A1:F1')->applyFromArray($headerMainStyle);
                $sheet->mergeCells('A1:F1');
                $sheet->getRowDimension(1)->setRowHeight(45);
                
                // Estilo para información general (fila 2)
                $statsStyle = [
                    'font' => [
                        'size' => 11,
                        'color' => ['rgb' => '1F2937']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EFF6FF']
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'BFDBFE']
                        ]
                    ]
                ];
                
                if ($sheet->getCell('A2')->getValue() && stripos($sheet->getCell('A2')->getValue(), 'Período') !== false) {
                    $sheet->getStyle('A2:F2')->applyFromArray($statsStyle);
                    $sheet->mergeCells('A2:F2');
                    $sheet->getRowDimension(2)->setRowHeight(35);
                }
                
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
                    $sheet->getRowDimension(4)->setRowHeight(35);
                    
                    // Aplicar estilos a filas de datos
                    for ($dataRow = 5; $dataRow <= $highestRow; $dataRow++) {
                        $nextCell = $sheet->getCell('A' . $dataRow)->getValue();
                        // Si encontramos otro encabezado o título, parar
                        if (is_string($nextCell) && (stripos($nextCell, 'TICKETS POR GERENCIA Y RESPONSABLE') !== false || stripos($nextCell, 'TOTALES POR EMPLEADO') !== false || ($sheet->getCell('A' . $dataRow)->getValue() && stripos($sheet->getCell('A' . $dataRow)->getValue(), 'Empleado') !== false && $dataRow > 4))) {
                            break;
                        }
                        
                        // Solo aplicar si la fila tiene datos
                        if ($sheet->getCell('A' . $dataRow)->getValue() !== null && $sheet->getCell('A' . $dataRow)->getValue() !== '') {
                            // Estilo general para toda la fila
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
                            
                            // Columna F (Responsable) con estilo especial y formato mejorado
                            $responsableValue = $sheet->getCell('F' . $dataRow)->getValue();
                            if ($responsableValue && $responsableValue !== '-' && stripos($responsableValue, 'Sin responsable') === false) {
                                // Aplicar formato con texto enriquecido si es posible
                                $sheet->getStyle('F' . $dataRow)->applyFromArray([
                                    'alignment' => [
                                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                        'wrapText' => true
                                    ],
                                    'font' => [
                                        'size' => 10
                                    ]
                                ]);
                                
                                // Intentar aplicar formato de texto enriquecido
                                try {
                                    $richText = new RichText();
                                    $lines = explode("\n", $responsableValue);
                                    foreach ($lines as $lineIndex => $line) {
                                        if (trim($line)) {
                                            if ($lineIndex > 0) {
                                                $richText->createText("\n");
                                            }
                                            // Buscar el separador "→"
                                            if (strpos($line, '→') !== false) {
                                                $parts = explode('→', $line, 2);
                                                $name = trim($parts[0]);
                                                $count = trim($parts[1] ?? '');
                                                
                                                $namePart = $richText->createTextRun($name);
                                                $namePart->getFont()->setBold(true)->setColor(new Color('1E40AF'));
                                                
                                                if ($count) {
                                                    $richText->createText(' → ');
                                                    $countPart = $richText->createTextRun($count);
                                                    $countPart->getFont()->setBold(true)->setColor(new Color('059669'));
                                                }
                                            } else {
                                                $richText->createText($line);
                                            }
                                        }
                                    }
                                    $sheet->getCell('F' . $dataRow)->setValue($richText);
                                } catch (\Exception $e) {
                                    // Si falla, mantener el valor original
                                }
                            } else {
                                $sheet->getStyle('F' . $dataRow)->applyFromArray([
                                    'alignment' => [
                                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                        'wrapText' => true
                                    ],
                                    'font' => [
                                        'italic' => true,
                                        'color' => ['rgb' => '9CA3AF']
                                    ]
                                ]);
                            }
                            
                            // Colores para columnas numéricas
                            $resueltosCell = $sheet->getCell('C' . $dataRow)->getValue();
                            if ($resueltosCell && is_numeric($resueltosCell)) {
                                $sheet->getStyle('C' . $dataRow)->applyFromArray([
                                    'font' => [
                                        'bold' => true,
                                        'color' => ['rgb' => '059669']
                                    ],
                                    'alignment' => [
                                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                                    ]
                                ]);
                            }
                            
                            $enProgresoCell = $sheet->getCell('D' . $dataRow)->getValue();
                            if ($enProgresoCell && is_numeric($enProgresoCell)) {
                                $sheet->getStyle('D' . $dataRow)->applyFromArray([
                                    'font' => [
                                        'bold' => true,
                                        'color' => ['rgb' => '2563EB']
                                    ],
                                    'alignment' => [
                                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                                    ]
                                ]);
                            }
                            
                            $pendientesCell = $sheet->getCell('E' . $dataRow)->getValue();
                            if ($pendientesCell && is_numeric($pendientesCell)) {
                                $sheet->getStyle('E' . $dataRow)->applyFromArray([
                                    'font' => [
                                        'bold' => true,
                                        'color' => ['rgb' => 'D97706']
                                    ],
                                    'alignment' => [
                                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                                    ]
                                ]);
                            }
                            
                            // Filas alternadas
                            if (($dataRow - 4) % 2 == 0) {
                                $sheet->getStyle('A' . $dataRow . ':F' . $dataRow)->applyFromArray([
                                    'fill' => [
                                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                        'startColor' => ['rgb' => 'F9FAFB']
                                    ]
                                ]);
                            }
                            
                            // Ajustar altura de fila si tiene múltiples responsables (columna F)
                            if ($responsableValue && stripos($responsableValue, 'ticket') !== false) {
                                // Calcular altura basada en número de líneas
                                $lineCount = substr_count($responsableValue, "\n") + 1;
                                $sheet->getRowDimension($dataRow)->setRowHeight(max(35, $lineCount * 22));
                            } else {
                                $sheet->getRowDimension($dataRow)->setRowHeight(35);
                            }
                        }
                    }
                }
                
                // Buscar y estilizar tabla de gerencia y responsable
                for ($row = 1; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell('A' . $row)->getValue();
                    if (is_string($cellValue) && stripos($cellValue, 'TICKETS POR GERENCIA Y RESPONSABLE') !== false) {
                        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($headerMainStyle);
                        $sheet->mergeCells('A' . $row . ':F' . $row);
                        $sheet->getRowDimension($row)->setRowHeight(30);
                        
                        // Estilizar encabezado de tabla (siguiente fila)
                        $nextRowValue = $sheet->getCell('A' . ($row + 1))->getValue();
                        if ($nextRowValue && stripos($nextRowValue, 'Gerencia') !== false) {
                            $sheet->getStyle('A' . ($row + 1) . ':F' . ($row + 1))->applyFromArray($headerStyle);
                            $sheet->getRowDimension($row + 1)->setRowHeight(25);
                            
                            // Aplicar estilos a filas de datos
                            for ($dataRow = $row + 2; $dataRow <= $highestRow; $dataRow++) {
                                $nextCell = $sheet->getCell('A' . $dataRow)->getValue();
                                // Si encontramos otro encabezado o título, parar
                                if (is_string($nextCell) && (stripos($nextCell, 'TOTALES POR EMPLEADO') !== false || ($sheet->getCell('A' . $dataRow)->getValue() && stripos($sheet->getCell('A' . $dataRow)->getValue(), 'Empleado') !== false && $dataRow > ($row + 1)))) {
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
                                    
                                    if (($dataRow - ($row + 1)) % 2 == 0) {
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

