<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ReporteExport implements FromView, WithEvents
{
    protected $datos;
    protected $columnas;

    public function __construct($datos, $columnas)
    {
        $this->datos = $datos;
        $this->columnas = $columnas;
    }

    public function view(): View
    {
        return view('reportes.exportExcel', [
            'datos' => $this->datos,
            'columnas' => $this->columnas,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $colCount = count($this->columnas);
                for ($col = 1; $col <= $colCount; $col++) {
                    $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->getColumnDimension($letter)->setAutoSize(true);
                }

                $rowCount = count($this->datos) + 2;
                for ($row = 2; $row <= $rowCount; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(-1);
                }
            }
        ];
    }
}
