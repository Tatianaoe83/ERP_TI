<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;

class TicketsSheetExport implements FromView, WithEvents, WithTitle, ShouldAutoSize
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

    public function view(): View
    {
        return view('tickets.export.tickets-excel', [
            'tickets' => $this->tickets,
            'resumen' => $this->resumen,
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
                
                // Autoajustar todas las columnas con límites mínimos y máximos
                $highestColumn = $sheet->getHighestColumn();
                $columnWidths = [
                    'A' => 12,  // # Ticket
                    'B' => 40,  // Descripción (más ancha)
                    'C' => 18,  // Fecha Creación
                    'D' => 20,  // Fecha Inicio Progreso
                    'E' => 20,  // Fecha Fin Progreso
                    'F' => 18,  // Tiempo Respuesta
                    'G' => 18,  // Tiempo Resolución
                    'H' => 12,  // Prioridad
                    'I' => 15,  // Estado
                    'J' => 25,  // Gerencia
                    'K' => 25,  // Empleado Creador
                    'L' => 30,  // Correo Creador
                    'M' => 15,  // Teléfono Creador
                    'N' => 25,  // Empleado Resolutor
                    'O' => 20,  // Clasificación
                    'P' => 20,  // Subtipo
                    'Q' => 20,  // Tertipo
                    'R' => 15,  // Código AnyDesk
                    'S' => 12,  // Número
                ];
                
                foreach (range('A', $highestColumn) as $col) {
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
                
                $highestColumnLetter = $sheet->getHighestColumn();
                $sheet->getStyle('A1:' . $highestColumnLetter . '1')->applyFromArray($headerMainStyle);
                $sheet->mergeCells('A1:' . $highestColumnLetter . '1');
                $sheet->getRowDimension(1)->setRowHeight(45);
                
                // Estilo para fila de estadísticas (fila 2)
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
                    $sheet->getStyle('A2:' . $highestColumnLetter . '2')->applyFromArray($statsStyle);
                    $sheet->mergeCells('A2:' . $highestColumnLetter . '2');
                    $sheet->getRowDimension(2)->setRowHeight(35);
                }
                
                // Estilo para encabezados de tabla (fila 3)
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'size' => 10,
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
                
                // Buscar la fila de encabezados (fila 3)
                if ($sheet->getCell('A3')->getValue() && stripos($sheet->getCell('A3')->getValue(), 'Ticket') !== false) {
                    $sheet->getStyle('A3:' . $highestColumnLetter . '3')->applyFromArray($headerStyle);
                    $sheet->getRowDimension(3)->setRowHeight(35);
                    
                    // Congelar encabezados
                    $sheet->freezePane('A4');
                }
                
                // Aplicar estilos a todas las filas de datos
                $highestRow = $sheet->getHighestRow();
                for ($row = 4; $row <= $highestRow; $row++) {
                    $sheet->getStyle('A' . $row . ':' . $highestColumnLetter . $row)->applyFromArray([
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
                    
                    // Wrap text a descripción (columna B)
                    $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
                    
                    // Filas alternadas con mejor contraste
                    if (($row - 3) % 2 == 0) {
                        $sheet->getStyle('A' . $row . ':' . $highestColumnLetter . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F9FAFB']
                            ]
                        ]);
                    }
                    
                    // Aplicar colores condicionales a Estado (columna I) y Prioridad (columna H)
                    // Prioridad está en la columna 8 (H), Estado en la columna 9 (I)
                    $prioridadCell = $sheet->getCell('H' . $row)->getValue();
                    $estadoCell = $sheet->getCell('I' . $row)->getValue();
                    
                    // Color para Prioridad (columna H)
                    if ($prioridadCell && $prioridadCell !== '-') {
                        $prioridadColor = '1F2937'; // Gris por defecto
                        $prioridadText = strtolower(trim($prioridadCell));
                        if (stripos($prioridadText, 'alta') !== false) {
                            $prioridadColor = 'DC2626'; // Rojo
                        } elseif (stripos($prioridadText, 'media') !== false) {
                            $prioridadColor = 'D97706'; // Naranja
                        } elseif (stripos($prioridadText, 'baja') !== false) {
                            $prioridadColor = '059669'; // Verde
                        }
                        $sheet->getStyle('H' . $row)->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => $prioridadColor]
                            ]
                        ]);
                    }
                    
                    // Color para Estado (columna I)
                    if ($estadoCell && $estadoCell !== '-') {
                        $estadoColor = '1F2937'; // Gris por defecto
                        $estadoText = strtolower(trim($estadoCell));
                        if (stripos($estadoText, 'cerrado') !== false) {
                            $estadoColor = '059669'; // Verde
                        } elseif (stripos($estadoText, 'en progreso') !== false || stripos($estadoText, 'progreso') !== false) {
                            $estadoColor = '2563EB'; // Azul
                        } elseif (stripos($estadoText, 'pendiente') !== false) {
                            $estadoColor = 'D97706'; // Naranja
                        }
                        $sheet->getStyle('I' . $row)->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => $estadoColor]
                            ]
                        ]);
                    }
                }
            }
        ];
    }
}

