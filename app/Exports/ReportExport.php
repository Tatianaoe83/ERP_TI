<?php

namespace App\Exports;


use App\Models\Gerencia;
use App\Models\Empleados;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class ReportExport implements FromView,ShouldAutoSize
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

                $numerogerencia = (int)$this->gerencia;

                $GerenciaTb = Empleados::query()
                ->select("gerencia.NombreGerencia","gerencia.NombreGerente",DB::raw('COUNT(DISTINCT empleados.EmpleadoID) AS CantidadEmpleados'))
                ->join("puestos", "empleados.PuestoID", "=", "puestos.PuestoID")
                ->join("departamentos", "departamentos.DepartamentoID", "=", "puestos.DepartamentoID")
                ->join("gerencia", "departamentos.GerenciaID", "=", "gerencia.GerenciaID")
                ->where('gerencia.GerenciaID','=', $numerogerencia)
                ->groupBy('gerencia.GerenciaID')
                ->get();


                $datosheader = DB::select('call sp_ReporteCostosPorGerenciaID(?)', [$numerogerencia]);


                $presup_otrosinsums = $this->tipo == 'mens' ? DB::select('call sp_GenerarReporteAccesoriosYMantenimientosPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_GenerarReporteAccesoriosYMantenimientosPorGerenciaAnual(?)',[$numerogerencia]);
                $presup_acces =  $this->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasVozPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_ReportePresupuestoLineasVozPorGerenciaAnual(?)',[$numerogerencia]);
                $presup_datos = $this->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasDatosPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_ReportePresupuestoLineasDatosPorGerenciaAnual(?)',[$numerogerencia]);
                $presup_gps = $this->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasGPSPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_ReportePresupuestoLineasGPSPorGerenciaAnual(?)',[$numerogerencia]);
                $presup_lics  = $this->tipo == 'mens' ? DB::select('call sp_GenerarReporteLicenciasPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_GenerarReporteLicenciasPorGerenciaAnual(?)',[$numerogerencia]);
            
                $tablapresup_otrosinsums = [];
                $columnaspresup_otrosinsums = [];
                $totalespresup_otrosinsums = []; 
                $granTotalpresup_otrosinsums = 0; 

                foreach ($presup_otrosinsums as $row) {
                    $empleadoID = $row->EmpleadoID;
                    $nombre = $row->NombreEmpleado;
                    $puesto = $row->NombrePuesto;
                    $insumo = $row->NombreInsumo;
                    $costo = (float) $row->CostoTotal;

                    if (!isset($tablapresup_otrosinsums[$empleadoID])) {
                        $tablapresup_otrosinsums[$empleadoID] = [
                            'NombreEmpleado' => $nombre,
                            'NombrePuesto' => $puesto,
                            'TotalPorEmpleado' => 0
                        ];
                    }

                    $tablapresup_otrosinsums[$empleadoID][$insumo] = $costo;
                    $tablapresup_otrosinsums[$empleadoID]['TotalPorEmpleado'] += $costo;

                  
                    $columnaspresup_otrosinsums[$insumo] = true;

                    if (!isset($totalespresup_otrosinsums[$insumo])) {
                        $totalespresup_otrosinsums[$insumo] = 0;
                    }
                    $totalespresup_otrosinsums[$insumo] += $costo;
                    
                    $granTotalpresup_otrosinsums += $costo;
                }


                $tablapresup_lics = [];
                $columnaspresup_lics = [];
                $totalespresup_lics = []; 
                $granTotalpresup_lics = 0; 

                foreach ($presup_lics as $row) {
                    $empleadoID = $row->EmpleadoID;
                    $nombre = $row->NombreEmpleado;
                    $puesto = $row->NombrePuesto;
                    $insumo = $row->NombreInsumo;
                    $costo = (float) $row->CostoTotal;

                    if (!isset($tablapresup_lics[$empleadoID])) {
                        $tablapresup_lics[$empleadoID] = [
                            'NombreEmpleado' => $nombre,
                            'NombrePuesto' => $puesto,
                            'TotalPorEmpleado' => 0
                        ];
                    }

                    $tablapresup_lics[$empleadoID][$insumo] = $costo;
                    $tablapresup_lics[$empleadoID]['TotalPorEmpleado'] += $costo;

                  
                    $columnaspresup_lics[$insumo] = true;

                    if (!isset($totalespresup_lics[$insumo])) {
                        $totalespresup_lics[$insumo] = 0;
                    }
                    $totalespresup_lics[$insumo] += $costo;
                    
                    $granTotalpresup_lics += $costo;
                }

              
              
                $data = ["title" => $this->tipo == 'mens' ? 'MENSUAL' : 'ANUAL',
                        "dato" => $this->tipo == 'mens' ? 'Mensual' : 'Anual',
                        'datosheader' => $datosheader,
                        'GerenciaTb' => $GerenciaTb[0],
                        'tablapresup_otrosinsums' => $tablapresup_otrosinsums,
                        'columnaspresup_otrosinsums' => $columnaspresup_otrosinsums,
                        'totalespresup_otrosinsums' => $totalespresup_otrosinsums,
                        'granTotalpresup_otrosinsums' => $granTotalpresup_otrosinsums,
                        'tablapresup_lics' => $tablapresup_lics,
                        'columnaspresup_lics' => $columnaspresup_lics,
                        'totalespresup_lics' => $totalespresup_lics,
                        'granTotalpresup_lics' => $granTotalpresup_lics,
                        'presup_lics' => [],
                        'presup_acces' => $presup_acces,
                        'presup_datos' => $presup_datos,
                        'presup_gps' => $presup_gps,
                        'presup_cal_pagos' => []
                        ];

        return view('presupuesto.reporteExcel', $data);
    }



        
        

}
