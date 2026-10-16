<?php

namespace App\Exports;


use App\Helpers\PresupuestoHelper;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;




class ReportExport implements FromView, WithColumnWidths, WithStyles
{

    private $gerencia;
    private $tipo;
    private $modo;

    // Columnas que ocupa el reporte (la tabla más ancha); se calcula en view()
    private $totalColumnas = 13;



    public function __construct(int $gerencia, string $tipo, string $modo = 'presupuesto')
    {
        $this->gerencia  = $gerencia;
        $this->tipo = $tipo;
        $this->modo = $modo === 'inventario' ? 'inventario' : 'presupuesto';
    }



    /**
     * Mismos datos que el PDF (PresupuestoHelper::datosReporte), así los conteos cuadran.
     */
    public function view(): View
    {
        $data = PresupuestoHelper::datosReporte((int) $this->gerencia, $this->tipo, $this->modo);

        // Empleado + Puesto + columnas de insumo + Total; el calendario usa 13
        $this->totalColumnas = max(
            13,
            count($data['columnaspresup_lics']) + 3,
            count($data['columnashardware']) + 3,
            count($data['columnaspresup_otrosinsums']) + 3
        );
        $data['totalColumnas'] = $this->totalColumnas;

        return view('presupuesto.reporteExcel', $data);
    }

    public function columnWidths(): array
    {
        $anchos = ['A' => 38, 'B' => 32];
        for ($i = 3; $i <= $this->totalColumnas; $i++) {
            $anchos[Coordinate::stringFromColumnIndex($i)] = 18;
        }

        return $anchos;
    }

    public function styles($sheet)
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
        $sheet->setShowGridlines(false);
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        // Encabezado: kicker, título, meta y KPIs con algo de aire
        $sheet->getRowDimension(1)->setRowHeight(16);
        $sheet->getRowDimension(2)->setRowHeight(24);
    }
}
