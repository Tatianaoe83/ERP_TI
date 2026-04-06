<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SolicitudesSheetExport implements FromArray, WithEvents, WithTitle
{
    protected $solicitudes;
    protected $metricasSolicitudes;
    protected $mes;
    protected $anio;

    public function __construct($solicitudes, $metricasSolicitudes, $mes, $anio)
    {
        $this->solicitudes = $solicitudes;
        $this->metricasSolicitudes = $metricasSolicitudes;
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function title(): string
    {
        return 'Solicitudes';
    }

    public function array(): array
    {
        $mesNombre = Carbon::create($this->anio, $this->mes, 1)->locale('es')->translatedFormat('F');
        $mesCapitalizado = ucfirst($mesNombre);
        
        $rows = [];
        
        // Fila 1: Título principal
        $rows[] = ["Reporte de Solicitudes - {$mesCapitalizado} {$this->anio}"];
        
        // Fila 2: Estadísticas generales
        $promedioCot = round($this->metricasSolicitudes['promedio_cotizacion_horas'] ?? 0, 1);
        $promedioConfig = round($this->metricasSolicitudes['promedio_configuracion_dias'] ?? 0, 1);
        $totalSolicitudes = count($this->metricasSolicitudes['desglose'] ?? []);
        
        $rows[] = [
            "Período: {$mesCapitalizado} {$this->anio} | Total Solicitudes: {$totalSolicitudes} | " .
            "Promedio Cotización: {$promedioCot}h | " .
            "Promedio Configuración: {$promedioConfig}h"
        ];
        
        // Fila 3: Encabezados
        $rows[] = [
            '# Solicitud',
            'Fecha Creación',
            'Empleado',
            'Gerencia',
            'Proyecto',
            'Motivo',
            'Descripción',
            'Estatus',
            'Tiempo Cotización (h)',
            'Tiempo Config. (h)',
            'Tiempo Total (h)',
        ];
        
        // Filas de datos
        $desglose = $this->metricasSolicitudes['desglose'] ?? [];
        foreach ($desglose as $sol) {
            $rows[] = [
                $sol['id'] ?? '',
                $sol['fecha_creacion'] ?? '',
                $sol['empleado'] ?? '',
                $sol['gerencia_nombre'] ?? 'Sin Gerencia',
                $sol['proyecto'] ?? '',
                $sol['motivo'] ?? '',
                $sol['descripcion_motivo'] ?? '',
                $sol['estatus'] ?? '',
                $sol['tiempo_cotizacion_horas'] !== null ? round($sol['tiempo_cotizacion_horas'], 1) : '-',
                $sol['tiempo_configuracion_dias'] !== null ? round($sol['tiempo_configuracion_dias'], 1) : '-',
                $sol['tiempo_total_dias'] !== null ? round($sol['tiempo_total_dias'], 1) : '-',
            ];
        }
        
        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Anchos de columnas
                $sheet->getColumnDimension('A')->setWidth(12);  // # Solicitud
                $sheet->getColumnDimension('B')->setWidth(18);  // Fecha Creación
                $sheet->getColumnDimension('C')->setWidth(30);  // Empleado
                $sheet->getColumnDimension('D')->setWidth(25);  // Gerencia
                $sheet->getColumnDimension('E')->setWidth(20);  // Proyecto
                $sheet->getColumnDimension('F')->setWidth(25);  // Motivo
                $sheet->getColumnDimension('G')->setWidth(45);  // Descripción
                $sheet->getColumnDimension('H')->setWidth(20);  // Estatus
                $sheet->getColumnDimension('I')->setWidth(18);  // Tiempo Cotización
                $sheet->getColumnDimension('J')->setWidth(18);  // Tiempo Config
                $sheet->getColumnDimension('K')->setWidth(18);  // Tiempo Total
                
                $highestColumn = $sheet->getHighestColumn();
                
                // Estilo para encabezado principal (fila 1)
                $headerMainStyle = [
                    'font' => [
                        'bold' => true,
                        'size' => 18,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '059669'] // Verde para solicitudes
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THICK,
                            'color' => ['rgb' => '047857']
                        ]
                    ]
                ];
                
                $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray($headerMainStyle);
                $sheet->mergeCells('A1:' . $highestColumn . '1');
                $sheet->getRowDimension(1)->setRowHeight(45);
                
                // Estilo para fila de estadísticas (fila 2)
                $statsStyle = [
                    'font' => [
                        'size' => 11,
                        'color' => ['rgb' => '1F2937']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D1FAE5'] // Verde claro
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '6EE7B7']
                        ]
                    ]
                ];
                
                $sheet->getStyle('A2:' . $highestColumn . '2')->applyFromArray($statsStyle);
                $sheet->mergeCells('A2:' . $highestColumn . '2');
                $sheet->getRowDimension(2)->setRowHeight(35);
                
                // Estilo para encabezados de tabla (fila 3)
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '10B981'] // Verde medio
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '047857']
                        ]
                    ]
                ];
                
                $sheet->getStyle('A3:' . $highestColumn . '3')->applyFromArray($headerStyle);
                $sheet->getRowDimension(3)->setRowHeight(35);
                
                // Congelar encabezados
                $sheet->freezePane('A4');
                
                // Aplicar estilos a todas las filas de datos
                $highestRow = $sheet->getHighestRow();
                for ($row = 4; $row <= $highestRow; $row++) {
                    $sheet->getStyle('A' . $row . ':' . $highestColumn . $row)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E5E7EB']
                            ]
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_TOP,
                            'wrapText' => true
                        ]
                    ]);
                    
                    // Wrap text a descripción (columna G)
                    $sheet->getStyle('G' . $row)->getAlignment()->setWrapText(true);
                    
                    // Filas alternadas
                    if (($row - 3) % 2 == 0) {
                        $sheet->getStyle('A' . $row . ':' . $highestColumn . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F9FAFB']
                            ]
                        ]);
                    }
                    
                    // Colores condicionales para Estatus (columna H)
                    $estatus = $sheet->getCell('H' . $row)->getValue();
                    $estatusLower = strtolower((string)$estatus);
                    
                    if (stripos($estatusLower, 'aprobad') !== false || stripos($estatusLower, 'completad') !== false) {
                        $sheet->getStyle('H' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'D1FAE5']
                            ],
                            'font' => ['color' => ['rgb' => '065F46'], 'bold' => true]
                        ]);
                    } elseif (stripos($estatusLower, 'rechazad') !== false || stripos($estatusLower, 'cancelad') !== false) {
                        $sheet->getStyle('H' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FEE2E2']
                            ],
                            'font' => ['color' => ['rgb' => '991B1B'], 'bold' => true]
                        ]);
                    } elseif (stripos($estatusLower, 'pendiente') !== false || stripos($estatusLower, 'revisión') !== false) {
                        $sheet->getStyle('H' . $row)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FEF3C7']
                            ],
                            'font' => ['color' => ['rgb' => '92400E'], 'bold' => true]
                        ]);
                    }
                    
                    // Centrar números y tiempos (columnas A, I, J, K)
                    foreach (['A', 'I', 'J', 'K'] as $col) {
                        $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }
            }
        ];
    }
}
