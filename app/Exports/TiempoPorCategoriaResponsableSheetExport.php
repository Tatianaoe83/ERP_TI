<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;

class TiempoPorCategoriaResponsableSheetExport implements FromView, WithEvents, WithTitle, ShouldAutoSize
{
    protected $datos;
    protected $mes;
    protected $anio;

    public function __construct($datos, $mes, $anio)
    {
        $this->datos = $datos;
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function view(): View
    {
        return view('tickets.export.tiempo-por-categoria-responsable-excel', [
            'datos' => $this->datos,
            'mes' => $this->mes,
            'anio' => $this->anio
        ]);
    }

    public function title(): string
    {
        return 'Tiempo por Categoría';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Configurar anchos de columna
                $columnWidths = [
                    'A' => 25,  // Tipo
                    'B' => 25,  // Subtipo
                    'C' => 25,  // Tertipo
                    'D' => 30,  // Responsable
                    'E' => 15,  // Total Tickets
                    'F' => 18,  // Tiempo Promedio (h)
                    'G' => 18,  // Tiempo Mínimo (h)
                    'H' => 18,  // Tiempo Máximo (h)
                    'I' => 18,  // Tiempo Total (h)
                ];
                
                foreach (range('A', 'I') as $col) {
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
                $sheet->getStyle('A1:I1')->applyFromArray($headerMainStyle);
                $sheet->mergeCells('A1:I1');
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
                    $sheet->getStyle('A2:I2')->applyFromArray($statsStyle);
                    $sheet->mergeCells('A2:I2');
                    $sheet->getRowDimension(2)->setRowHeight(35);
                }
                
                // Estilo para encabezados de tabla (fila 3)
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
                
                // Buscar fila de encabezado de tabla (fila 3)
                $highestRow = $sheet->getHighestRow();
                if ($sheet->getCell('A3')->getValue() && stripos($sheet->getCell('A3')->getValue(), 'Tipo') !== false) {
                    $sheet->getStyle('A3:I3')->applyFromArray($headerStyle);
                    $sheet->getRowDimension(3)->setRowHeight(35);
                    
                    // Aplicar estilos a filas de datos
                    for ($dataRow = 4; $dataRow <= $highestRow; $dataRow++) {
                        if ($sheet->getCell('A' . $dataRow)->getValue() !== null && $sheet->getCell('A' . $dataRow)->getValue() !== '') {
                            $sheet->getStyle('A' . $dataRow . ':I' . $dataRow)->applyFromArray([
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
                            
                            // Filas alternadas
                            if (($dataRow - 3) % 2 == 0) {
                                $sheet->getStyle('A' . $dataRow . ':I' . $dataRow)->applyFromArray([
                                    'fill' => [
                                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                        'startColor' => ['rgb' => 'F9FAFB']
                                    ]
                                ]);
                            }
                            
                            // Estilo para columnas numéricas
                            $sheet->getStyle('E' . $dataRow . ':I' . $dataRow)->applyFromArray([
                                'alignment' => [
                                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                                ]
                            ]);
                            
                            $sheet->getRowDimension($dataRow)->setRowHeight(30);
                        }
                    }
                }
            }
        ];
    }
}

