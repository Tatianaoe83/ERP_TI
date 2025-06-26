<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStartRow;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ReporteExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithDrawings, WithStartRow
{
    protected $datos;
    protected $columnas;

    public function __construct($datos, $columnas)
    {
        $this->datos = $datos;
        $this->columnas = $columnas;
    }

    public function startRow(): int
    {
        return 8;
    }

    public function collection()
    {
        return collect($this->datos);
    }

    public function headings(): array
    {
        return $this->columnas;
    }

    public function styles(Worksheet $sheet)
    {
        $colCount = count($this->columnas);
        $rowCount = $this->datos->count() + $this->startRow();
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        $start = $this->startRow();

        $sheet->getStyle("A{$this->startRow()}:{$lastCol}{$rowCount}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);

        $sheet->getStyle("A{$start}:{$lastCol}{$start}")
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

        $sheet->mergeCells("A6:{$lastCol}6");
        $sheet->setCellValue("A6", "Reporte generado automáticamente");
        $sheet->getStyle("A6")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        return [];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo de la empresa');
        $drawing->setPath(public_path('img/logo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetY(5);

        return [$drawing];
    }
}
