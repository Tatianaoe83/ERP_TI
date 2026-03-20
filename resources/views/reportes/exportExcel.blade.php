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
        if ($datos instanceof \Illuminate\Database\Query\Builder ||
            $datos instanceof \Illuminate\Database\Eloquent\Builder) {
            $this->datos = $datos->chunk(1000, function ($filas) {
                return $filas;
            });
            $this->datos = $datos->lazy(1000);
        } else {
            $this->datos = $datos;
        }

        $this->columnas = $columnas;
    }

    public function view(): View
    {
        return view('reportes.exportExcel', [
            'datos'    => $this->datos,
            'columnas' => $this->columnas,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $colCount = count($this->columnas);

                for ($col = 1; $col <= $colCount; $col++) {
                    $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $sheet->getColumnDimension($letter)->setAutoSize(true);
                }

            }
        ];
    }
}