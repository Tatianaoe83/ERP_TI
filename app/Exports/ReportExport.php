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

    private $gerencia;
    private $tipo;

    public function __construct(int $gerencia,string $tipo)
    {
        $this->gerencia  = $gerencia;
        $this->tipo = $tipo;
    }


    
    /**
    * @return \Illuminate\Support\Collection
    */
     public function view(): View
    {
        $GerenciaTb = Gerencia::query()
                ->select("*")
                ->where('GerenciaID','=',$this->gerencia)
                ->get();

             $datosheader = DB::select('EXECUTE sp_ReporteCostosPorGerenciaID @GerenciaID ='.''. $this->gerencia);
             $presup_lics =  $this->tipo == 'mens' ? DB::select('EXECUTE sp_GenerarReporteLicenciasPorGerencia @GerenciaID ='.''. $this->gerencia ) : DB::select('EXECUTE sp_GenerarReporteLicenciasPorGerenciaAnual @GerenciaID ='.''. $this->gerencia);
             $presup_otrosinsums =  $this->tipo == 'mens' ? DB::select('EXECUTE sp_GenerarReporteAccesoriosYMantenimientosPorGerencia @GerenciaID ='.''. $this->gerencia ) : DB::select('EXECUTE sp_GenerarReporteAccesoriosYMantenimientosPorGerenciaAnual @GerenciaID ='.''. $this->gerencia);
             $presup_acces =  $this->tipo == 'mens' ? DB::select('EXECUTE sp_ReportePresupuestoLineasVozPorGerencia @GerenciaID ='.''. $this->gerencia ) : DB::select('EXECUTE sp_ReportePresupuestoLineasVozPorGerenciaAnual @GerenciaID ='.''. $this->gerencia);
             $presup_datos = $this->tipo == 'mens' ? DB::select('EXECUTE sp_ReportePresupuestoLineasDatosPorGerencia @GerenciaID ='.''. $this->gerencia) : DB::select('EXECUTE sp_ReportePresupuestoLineasDatosPorGerenciaAnual @GerenciaID ='.''. $this->gerencia);
             $presup_gps = $this->tipo == 'mens' ? DB::select('EXECUTE sp_ReportePresupuestoLineasGPSPorGerencia @GerenciaID ='.''. $this->gerencia) : DB::select('EXECUTE sp_ReportePresupuestoLineasGPSPorGerenciaAnual @GerenciaID ='.''. $this->gerencia);
             $presup_cal_pagos = DB::select('EXECUTE ObtenerInsumosAnualesPorGerencia @GerenciaID ='.''. $this->gerencia);
             
             $data = ["title" => $this->tipo == 'mens' ? 'MENSUAL' : 'ANUAL',
                        "dato" => $this->tipo == 'mens' ? 'Mensual' : 'Anual',
                        'datosheader' => $datosheader[0],
                        'GerenciaTb' => $GerenciaTb,
                        'presup_otrosinsums' => $presup_otrosinsums,
                        'presup_lics' => $presup_lics,
                        'presup_acces' => $presup_acces,
                        'presup_datos' => $presup_datos,
                        'presup_gps' => $presup_gps,
                        'presup_cal_pagos' => $presup_cal_pagos
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
