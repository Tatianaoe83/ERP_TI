<?php

namespace App\Exports;


use App\Models\Gerencia;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class ReportExport implements FromView,ShouldAutoSize,WithStyles
{

    private $year;
    private $tipo;

    public function __construct(int $year,string $tipo)
    {
        $this->year  = $year;
        $this->tipo = $tipo;
    }


    
    /**
    * @return \Illuminate\Support\Collection
    */
     public function view(): View
    {
        $GerenciaTb = Gerencia::query()
                ->select("*")
                ->where('GerenciaID','=',$this->year)
                ->get();

                $presup_acces = $this->tipo == 'mens' ? 
                    DB::select('call sp_ReportePresupuestoLineasVozPorGerencia(?)',[$this->year]) : 
                    DB::select('call sp_ReportePresupuestoLineasVozPorGerenciaAnual(?)',[$this->year]);
                $presup_datos = $this->tipo == 'mens' ? 
                    DB::select('call sp_ReportePresupuestoLineasDatosPorGerencia(?)',[$this->year]) : 
                    DB::select('call sp_ReportePresupuestoLineasDatosPorGerenciaAnual(?)',[$this->year]);
                $presup_gps = $this->tipo == 'mens' ? 
                    DB::select('call sp_ReportePresupuestoLineasGPSPorGerencia(?)',[$this->year]) : 
                    DB::select('call sp_ReportePresupuestoLineasGPSPorGerenciaAnual(?)',[$this->year]);
            

        $data = [
            "title" => $this->tipo == 'mens' ? 'MENSUAL' : 'ANUAL',
            "dato" => $this->tipo == 'mens' ? 'Mensual' : 'Anual',
            'GerenciaTb' => $GerenciaTb,
            'presup_acces' => $presup_acces,
            'presup_datos' => $presup_datos,
            'presup_gps' => $presup_gps,
        ];

        return view('presupuesto.reporteExcel', $data);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Encabezados de la tabla (negrita y fondo azul)
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '191970']]],
            
           
        ];
    }

        
        

}
