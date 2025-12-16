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
                
                // Autoajustar todas las columnas
                $highestColumn = $sheet->getHighestColumn();
                foreach (range('A', $highestColumn) as $col) {
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
                
                $highestColumnLetter = $sheet->getHighestColumn();
                $sheet->getStyle('A1:' . $highestColumnLetter . '1')->applyFromArray($headerMainStyle);
                $sheet->mergeCells('A1:' . $highestColumnLetter . '1');
                $sheet->getRowDimension(1)->setRowHeight(40);
                
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
                    $sheet->getRowDimension(3)->setRowHeight(30);
                    
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
                    
                    // Filas alternadas
                    if (($row - 3) % 2 == 0) {
                        $sheet->getStyle('A' . $row . ':' . $highestColumnLetter . $row)->applyFromArray([
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

