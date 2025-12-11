<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class ReporteMensualTicketsExport implements WithMultipleSheets
{
    protected $tickets;
    protected $resumen;
    protected $mes;
    protected $anio;

    public function __construct($tickets, $resumen, $mes, $anio)
    {
        $this->tickets = $tickets;
        $this->resumen = $resumen;
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function sheets(): array
    {
        return [
            new ResumenSheetExport($this->resumen, $this->mes, $this->anio),
            new TicketsSheetExport($this->tickets, $this->mes, $this->anio),
        ];
    }
}

class ResumenSheetExport implements FromView, WithEvents, WithTitle
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
                
                // Estilo para encabezados principales
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
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ]
                ];
                
                // Aplicar a encabezado principal
                $sheet->getStyle('A1:F1')->applyFromArray($headerMainStyle);
                $sheet->mergeCells('A1:F1');
                $sheet->getRowDimension(1)->setRowHeight(40);
                
                // Estilo para encabezados de tabla
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E3A8A']
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '1E40AF']
                        ]
                    ]
                ];
                
                // Ajustar ancho de columnas
                $sheet->getColumnDimension('A')->setWidth(30);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(30);
                
                // Estilo para la tabla de incidencias por gerencia (fila 5)
                $sheet->getStyle('A5:F5')->applyFromArray($headerStyle);
                
                // Aplicar bordes y estilos alternados a las filas de datos
                $highestRow = $sheet->getHighestRow();
                for ($row = 6; $row <= $highestRow; $row++) {
                    $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'E5E7EB']
                            ]
                        ]
                    ]);
                    // Filas alternadas
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F9FAFB']
                            ]
                        ]);
                    }
                }
                
                // Estilo para información general (fila 3)
                $sheet->getStyle('A3:F3')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB']
                        ]
                    ]
                ]);
                
                // Ajustar altura de filas
                $sheet->getRowDimension(1)->setRowHeight(50);
                $sheet->getRowDimension(3)->setRowHeight(80);
                $sheet->getRowDimension(5)->setRowHeight(25);
                
                // Buscar la fila donde comienza la tabla de totales por empleado
                $highestRow = $sheet->getHighestRow();
                $totalesEmpleadoRow = null;
                for ($row = 1; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell('A' . $row)->getValue();
                    if (is_string($cellValue) && strpos($cellValue, 'TOTALES POR EMPLEADO') !== false) {
                        $totalesEmpleadoRow = $row;
                        break;
                    }
                }
                
                if ($totalesEmpleadoRow) {
                    // Estilo para encabezado de totales por empleado
                    $sheet->getStyle('A' . $totalesEmpleadoRow . ':F' . $totalesEmpleadoRow)->applyFromArray($headerMainStyle);
                    $sheet->mergeCells('A' . $totalesEmpleadoRow . ':F' . $totalesEmpleadoRow);
                    $sheet->getRowDimension($totalesEmpleadoRow)->setRowHeight(30);
                    
                    // Estilo para encabezados de la tabla de empleados (siguiente fila)
                    if ($sheet->getCell('A' . ($totalesEmpleadoRow + 1))->getValue()) {
                        $sheet->getStyle('A' . ($totalesEmpleadoRow + 1) . ':F' . ($totalesEmpleadoRow + 1))->applyFromArray($headerStyle);
                        
                        // Aplicar estilos a las filas de datos de empleados
                        for ($row = $totalesEmpleadoRow + 2; $row <= $highestRow; $row++) {
                            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                                'borders' => [
                                    'allBorders' => [
                                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                        'color' => ['rgb' => 'E5E7EB']
                                    ]
                                ]
                            ]);
                            // Filas alternadas
                            if (($row - $totalesEmpleadoRow) % 2 == 0) {
                                $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                                    'fill' => [
                                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                        'startColor' => ['rgb' => 'F9FAFB']
                                    ]
                                ]);
                            }
                        }
                    }
                }
            }
        ];
    }
}

class TicketsSheetExport implements FromView, WithEvents, WithTitle
{
    protected $tickets;
    protected $mes;
    protected $anio;

    public function __construct($tickets, $mes, $anio)
    {
        $this->tickets = $tickets;
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function view(): View
    {
        return view('tickets.export.tickets-excel', [
            'tickets' => $this->tickets,
            'mes' => $this->mes,
            'anio' => $this->anio
        ]);
    }

    public function title(): string
    {
        return 'Tickets';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Ajustar ancho de columnas
                $sheet->getColumnDimension('A')->setWidth(12); // Número
                $sheet->getColumnDimension('B')->setWidth(45); // Descripción
                $sheet->getColumnDimension('C')->setWidth(18); // Fecha creación
                $sheet->getColumnDimension('D')->setWidth(18); // Fecha inicio
                $sheet->getColumnDimension('E')->setWidth(18); // Fecha fin
                $sheet->getColumnDimension('F')->setWidth(15); // Tiempo respuesta
                $sheet->getColumnDimension('G')->setWidth(15); // Tiempo resolución
                $sheet->getColumnDimension('H')->setWidth(12); // Prioridad
                $sheet->getColumnDimension('I')->setWidth(15); // Estado
                $sheet->getColumnDimension('J')->setWidth(25); // Gerencia
                $sheet->getColumnDimension('K')->setWidth(30); // Empleado creador
                $sheet->getColumnDimension('L')->setWidth(30); // Correo creador
                $sheet->getColumnDimension('M')->setWidth(18); // Teléfono creador
                $sheet->getColumnDimension('N')->setWidth(30); // Empleado resolutor
                $sheet->getColumnDimension('O')->setWidth(25); // Clasificación
                $sheet->getColumnDimension('P')->setWidth(25); // Subtipo
                $sheet->getColumnDimension('Q')->setWidth(25); // Tertipo
                $sheet->getColumnDimension('R')->setWidth(15); // Código AnyDesk
                $sheet->getColumnDimension('S')->setWidth(12); // Número
                
                // Estilo para encabezado principal
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
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ]
                ];
                
                // Aplicar a encabezado principal
                $sheet->getStyle('A1:S1')->applyFromArray($headerMainStyle);
                $sheet->mergeCells('A1:S1');
                $sheet->getRowDimension(1)->setRowHeight(50);
                
                // Estilo para encabezados de tabla
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'size' => 10,
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
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '1E40AF']
                        ]
                    ]
                ];
                $sheet->getStyle('A3:S3')->applyFromArray($headerStyle);
                $sheet->getRowDimension(3)->setRowHeight(40);
                
                // Congelar encabezados
                $sheet->freezePane('A4');

                // Aplicar estilos a todas las filas de datos
                $highestRow = $sheet->getHighestRow();
                for ($row = 4; $row <= $highestRow; $row++) {
                    // Aplicar bordes
                    $sheet->getStyle('A' . $row . ':S' . $row)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'E5E7EB']
                            ]
                        ]
                    ]);
                    
                    // Wrap text a descripción
                    $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
                    
                    // Colores alternados para filas
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A' . $row . ':S' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F9FAFB']
                            ]
                        ]);
                    }
                }
            }
        ];
    }
}

