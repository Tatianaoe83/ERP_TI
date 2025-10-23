<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Gerencia;
use App\Models\Gerencias_usuarios;
use App\Models\Empleados;
use PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;



class PresupuestoController extends Controller
{
   
    public function __construct()
    {
        $this->middleware('permission:ver-presupuesto')->only('index');
      
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {       

        $id = auth()->id();

        $usuariosgeren = DB::table('gerencias_usuarios')
            ->where('users_id', $id)
            ->pluck('GerenciaID')
            ->toArray();
        
        if (count($usuariosgeren) > 0) {
            $genusuarios = Gerencia::join("gerencias_usuarios", "gerencias_usuarios.GerenciaID", "=", "gerencia.GerenciaID")
                ->select('gerencia.*')
                ->where('gerencias_usuarios.users_id', $id)
                ->get(); 
        } else {
            $genusuarios = Gerencia::all();
        }
        
        return view('presupuesto.index', compact('genusuarios'));
        
      
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Blog $blog)
    {
      
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Blog $blog)
    {
      
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Blog $blog)
    {
      
    }


    public function descargar(request $request){


        $numerogerencia = (int) $request->GerenciaID;
        $GerenciaTb = Empleados::query()
        ->select(
            "gerencia.NombreGerencia",
            "gerencia.NombreGerente",
            DB::raw('COUNT(DISTINCT empleados.EmpleadoID) AS CantidadEmpleados')
        )
        ->join("puestos", "empleados.PuestoID", "=", "puestos.PuestoID")
        ->join("departamentos", "departamentos.DepartamentoID", "=", "puestos.DepartamentoID")
        ->rightJoin("gerencia", "departamentos.GerenciaID", "=", "gerencia.GerenciaID")
        ->where('gerencia.GerenciaID', '=', $numerogerencia)
        ->where('empleados.Estado', '=', 1)
        ->groupBy('gerencia.GerenciaID', 'gerencia.NombreGerencia', 'gerencia.NombreGerente')
        ->get();

       if ($request->submitbutton == 'pdf'){


                // Calcular totales directamente desde las tablas para verificar
                $presup_hardware  = $request->tipo == 'mens' ? DB::select('call sp_GenerarReporteHardwarePorGerencia(?)',[$numerogerencia]) : DB::select('call sp_GenerarReporteHardwarePorGerenciaAnual(?)',[$numerogerencia]);
                $presup_otrosinsums = $request->tipo == 'mens' ? DB::select('call sp_GenerarReporteAccesoriosYMantenimientosPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_GenerarReporteAccesoriosYMantenimientosPorGerenciaAnual(?)',[$numerogerencia]);
                $presup_lics  = $request->tipo == 'mens' ? DB::select('call sp_GenerarReporteLicenciasPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_GenerarReporteLicenciasPorGerenciaAnual(?)',[$numerogerencia]);
                $presup_acces =  $request->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasVozPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_ReportePresupuestoLineasVozPorGerenciaAnual(?)',[$numerogerencia]);
                $presup_datos = $request->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasDatosPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_ReportePresupuestoLineasDatosPorGerenciaAnual(?)',[$numerogerencia]);
                $presup_gps = $request->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasGPSPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_ReportePresupuestoLineasGPSPorGerenciaAnual(?)',[$numerogerencia]);
                
                
                //Sumar costos de Renta de Impresora
                if ($request->tipo == 'mens') {
                    // Consulta para costo mensual
                    $presup_impresoras = DB::select("
                        SELECT 
                'Costo Renta de Impresora' AS Categoria,
                 ROUND(SUM(DISTINCT IFNULL(ii.CostoAnual, 0)), 0) AS CostoTotal
                FROM inventarioinsumo ii
                INNER JOIN empleados e ON ii.EmpleadoID = e.EmpleadoID
                INNER JOIN puestos p ON e.PuestoID = p.PuestoID
                INNER JOIN departamentos d ON p.DepartamentoID = d.DepartamentoID
                INNER JOIN gerencia g ON d.GerenciaID = g.GerenciaID
                WHERE g.GerenciaID = ?
                    AND ii.CateogoriaInsumo = 'RENTA DE IMPRESORA'
                    ", [$numerogerencia]);

                    $presup_internet_fijo = DB::select("
                    SELECT 
            'Costo Internet Fijo' AS Categoria,
             ROUND(SUM(DISTINCT IFNULL(ii.CostoMensual, 0)), 0) AS CostoTotal
            FROM inventarioinsumo ii
            INNER JOIN empleados e ON ii.EmpleadoID = e.EmpleadoID
            INNER JOIN puestos p ON e.PuestoID = p.PuestoID
            INNER JOIN departamentos d ON p.DepartamentoID = d.DepartamentoID
            INNER JOIN gerencia g ON d.GerenciaID = g.GerenciaID
            WHERE g.GerenciaID = ?
                AND ii.CateogoriaInsumo = 'INTERNET'
                ", [$numerogerencia]);

                    
                } else {
                    // Consulta para costo anual
                    $presup_impresoras = DB::select("
                             SELECT 
                            'Costo Renta de Impresora' AS Categoria,
                            ROUND(SUM(CostoAnual * CantidadMeses), 0) AS CostoTotal
                            FROM (
                                SELECT ii.EmpleadoID, ii.NombreInsumo, ii.NumSerie, ii.CostoAnual,
                                       COUNT(*) as CantidadMeses
                                FROM inventarioinsumo ii
                                INNER JOIN empleados e ON ii.EmpleadoID = e.EmpleadoID
                                INNER JOIN puestos p ON e.PuestoID = p.PuestoID
                                INNER JOIN departamentos d ON p.DepartamentoID = d.DepartamentoID
                                INNER JOIN gerencia g ON d.GerenciaID = g.GerenciaID
                                WHERE g.GerenciaID = ?
                                    AND ii.CateogoriaInsumo = 'RENTA DE IMPRESORA'
                                GROUP BY ii.EmpleadoID, ii.NombreInsumo, ii.NumSerie, ii.CostoAnual
                            ) as impresoras_unicas
                    ", [$numerogerencia]);

                    $presup_internet_fijo = DB::select("
                    SELECT 
                   'Costo Internet Fijo' AS Categoria,
                   ROUND(SUM(DISTINCT IFNULL(ii.CostoAnual, 0)), 0) AS CostoTotal
                   FROM inventarioinsumo ii
                   INNER JOIN empleados e ON ii.EmpleadoID = e.EmpleadoID
                   INNER JOIN puestos p ON e.PuestoID = p.PuestoID
                   INNER JOIN departamentos d ON p.DepartamentoID = d.DepartamentoID
                   INNER JOIN gerencia g ON d.GerenciaID = g.GerenciaID
                   WHERE g.GerenciaID = ?
                       AND ii.CateogoriaInsumo = 'INTERNET'
           ", [$numerogerencia]);
                }

            

                // Calcular totales reales desde las tablas individuales
                $totalHardware = 0;
                foreach ($presup_hardware as $row) {
                    $totalHardware += (int) $row->CostoTotal;
                }

                $totalOtrosInsumos = 0;
                foreach ($presup_otrosinsums as $row) {
                    $totalOtrosInsumos += (int) $row->CostoTotal;
                }

                $totalLicencias = 0;
                foreach ($presup_lics as $row) {
                    $totalLicencias += (int) $row->CostoTotal;
                }

                $totalTelefonia = 0;
                foreach ($presup_acces as $row) {
                    // Calcular el total para cada empleado en telefonía
                    if ($request->tipo == 'mens') {
                        $row->Total = (int) $row->Voz_Costo_Renta_Mensual + (int) $row->Voz_Costo_Fianza + (int) $row->Voz_Monto_Renovacion;
                        $totalTelefonia += $row->Total;
                    } else {
                        $row->Total = (int) $row->Voz_Costo_Renta_Anual + (int) $row->Voz_Costo_Fianza_Anual + (int) $row->Voz_Monto_Renovacion_Anual;
                        $totalTelefonia += $row->Total;
                    }
                }

                $totalDatos = 0;
                foreach ($presup_datos as $row) {
                    // Calcular el total para cada empleado en datos
                    if ($request->tipo == 'mens') {
                        $row->Total = (int) $row->Datos_Costo_Renta_Mensual + (int) $row->Datos_Costo_Fianza + (int) $row->Datos_Monto_Renovacion;
                        $totalDatos += $row->Total;
                    } else {
                        $row->Total = (int) $row->Datos_Costo_Renta_Anual + (int) $row->Datos_Costo_Fianza_Anual + (int) $row->Datos_Monto_Renovacion_Anual;
                        $totalDatos += $row->Total;
                    }
                }

                $totalGPS = 0;
                foreach ($presup_gps as $row) {
                    // Calcular el total para cada empleado en GPS
                    if ($request->tipo == 'mens') {
                        $row->Total = (int) $row->GPS_Costo_Renta_Mensual + (int) $row->GPS_Costo_Fianza + (int) $row->GPS_Monto_Renovacion;
                        $totalGPS += $row->Total;
                    } else {
                        $row->Total = (int) $row->GPS_Costo_Renta_Anual + (int) $row->GPS_Costo_Fianza_Anual + (int) $row->GPS_Monto_Renovacion_Anual;
                        $totalGPS += $row->Total;
                    }
                }

                $totalImpresoras = 0;
                foreach ($presup_impresoras as $row) {
                   
                    // Calcular el total para impresoras
                    $totalImpresoras += (int) $row->CostoTotal ?? 0;
                }
                
              
                $totalInternetFijo = 0;
                foreach ($presup_internet_fijo as $row) {
                    // Calcular el total para internet fijo
                    $totalInternetFijo += (int) $row->CostoTotal ?? 0;
                }
                
               

                $totalCalculadoReal = $totalHardware + $totalOtrosInsumos + $totalLicencias + $totalTelefonia + $totalDatos + $totalGPS + $totalImpresoras + $totalInternetFijo;

                // Crear el array de datos del header con los totales reales calculados desde las consultas
                $datosheader = [
                    (object) [
                        'Categoria' => 'Costo Licenciamiento',
                        'TotalCosto' => $totalLicencias
                    ],
                    (object) [
                        'Categoria' => 'Costo Inversiones',
                        'TotalCosto' => $totalHardware
                    ],
                    (object) [
                        'Categoria' => 'Costo Otros Insumos',
                        'TotalCosto' => $totalOtrosInsumos
                    ],
                    (object) [
                        'Categoria' => 'Costo Telefonía e Internet',
                        'TotalCosto' => $totalTelefonia + $totalDatos
                    ],
                    (object) [
                        'Categoria' => 'Costo GPS',
                        'TotalCosto' => $totalGPS
                    ],
                    (object) [
                        'Categoria' => 'Costo Renta de Impresoras',
                        'TotalCosto' => $totalImpresoras
                    ],
                    (object) [
                        'Categoria' => 'Costo Internet fijo',
                        'TotalCosto' => $totalInternetFijo
                    ],
                    (object) [
                        'Categoria' => 'Total Presupuestado',
                        'TotalCosto' => $totalCalculadoReal
                    ]
                ];               
            
                $presup_cal_pagos = DB::select('call ObtenerInsumosAnualesPorGerencia(?)', [$numerogerencia]);

                // Inicializar las variables para las sumas de cada mes
                $sumaEnero = 0;
                $sumaFebrero = 0;
                $sumaMarzo = 0;
                $sumaAbril = 0;
                $sumaMayo = 0;
                $sumaJunio = 0;
                $sumaJulio = 0;
                $sumaAgosto = 0;
                $sumaSeptiembre = 0;
                $sumaOctubre = 0;
                $sumaNoviembre = 0;
                $sumaDiciembre = 0;
                
                // Recorrer los datos y sumar los valores por cada mes, además agregar el total por fila
                foreach ($presup_cal_pagos as $registro) {
                    // Calcular el total para cada insumo (suma horizontal de los 12 meses)
                    $totalFila = 0;
                    $totalFila += is_numeric($registro->Enero) ? $registro->Enero : 0;
                    $totalFila += is_numeric($registro->Febrero) ? $registro->Febrero : 0;
                    $totalFila += is_numeric($registro->Marzo) ? $registro->Marzo : 0;
                    $totalFila += is_numeric($registro->Abril) ? $registro->Abril : 0;
                    $totalFila += is_numeric($registro->Mayo) ? $registro->Mayo : 0;
                    $totalFila += is_numeric($registro->Junio) ? $registro->Junio : 0;
                    $totalFila += is_numeric($registro->Julio) ? $registro->Julio : 0;
                    $totalFila += is_numeric($registro->Agosto) ? $registro->Agosto : 0;
                    $totalFila += is_numeric($registro->Septiembre) ? $registro->Septiembre : 0;
                    $totalFila += is_numeric($registro->Octubre) ? $registro->Octubre : 0;
                    $totalFila += is_numeric($registro->Noviembre) ? $registro->Noviembre : 0;
                    $totalFila += is_numeric($registro->Diciembre) ? $registro->Diciembre : 0;
                    
                    // Agregar la columna Total al registro
                    $registro->Total = $totalFila;
                    
                    // Sumar para los totales verticales
                    $sumaEnero += is_numeric($registro->Enero) ? $registro->Enero : 0;
                    $sumaFebrero += is_numeric($registro->Febrero) ? $registro->Febrero : 0;
                    $sumaMarzo += is_numeric($registro->Marzo) ? $registro->Marzo : 0;
                    $sumaAbril += is_numeric($registro->Abril) ? $registro->Abril : 0;
                    $sumaMayo += is_numeric($registro->Mayo) ? $registro->Mayo : 0;
                    $sumaJunio += is_numeric($registro->Junio) ? $registro->Junio : 0;
                    $sumaJulio += is_numeric($registro->Julio) ? $registro->Julio : 0;
                    $sumaAgosto += is_numeric($registro->Agosto) ? $registro->Agosto : 0;
                    $sumaSeptiembre += is_numeric($registro->Septiembre) ? $registro->Septiembre : 0;
                    $sumaOctubre += is_numeric($registro->Octubre) ? $registro->Octubre : 0;
                    $sumaNoviembre += is_numeric($registro->Noviembre) ? $registro->Noviembre : 0;
                    $sumaDiciembre += is_numeric($registro->Diciembre) ? $registro->Diciembre : 0;
                }
                
                // Calcular el gran total (suma de todos los meses)
                $granTotal = $sumaEnero + $sumaFebrero + $sumaMarzo + $sumaAbril + $sumaMayo + $sumaJunio + 
                             $sumaJulio + $sumaAgosto + $sumaSeptiembre + $sumaOctubre + $sumaNoviembre + $sumaDiciembre;
                
                // Agregar la fila "Total"
                $presup_cal_pagos[] = (object) [
                    'NombreInsumo' => 'Total',
                    'Enero' => $sumaEnero,
                    'Febrero' => $sumaFebrero,
                    'Marzo' => $sumaMarzo,
                    'Abril' => $sumaAbril,
                    'Mayo' => $sumaMayo,
                    'Junio' => $sumaJunio,
                    'Julio' => $sumaJulio,
                    'Agosto' => $sumaAgosto,
                    'Septiembre' => $sumaSeptiembre,
                    'Octubre' => $sumaOctubre,
                    'Noviembre' => $sumaNoviembre,
                    'Diciembre' => $sumaDiciembre,
                    'Total' => $granTotal,
                    'Orden' => 7 
                ];
                
             
                $tablahardware = [];
                $columnashardware = [];
                $totaleshardware = []; 
                $granTotalhardware = 0; 

                // Agrupar por empleado e insumo para evitar duplicados
                $datosAgrupados = [];
                foreach ($presup_hardware as $row) {
                    $key = $row->EmpleadoID . '_' . $row->NombreInsumo;
                    if (!isset($datosAgrupados[$key])) {
                        $datosAgrupados[$key] = [
                            'EmpleadoID' => $row->EmpleadoID,
                            'NombreEmpleado' => $row->NombreEmpleado,
                            'NombrePuesto' => $row->NombrePuesto,
                            'NombreInsumo' => $row->NombreInsumo,
                            'CostoTotal' => (int) $row->CostoTotal
                        ];
                    } else {
                        // Si ya existe, sumar el costo (por si hay duplicados en el procedimiento)
                        $datosAgrupados[$key]['CostoTotal'] += (int) $row->CostoTotal;
                    }
                }

                foreach ($datosAgrupados as $key => $row) {
                    $empleadoID = $row['EmpleadoID'];
                    $nombre = $row['NombreEmpleado'];
                    $puesto = $row['NombrePuesto'];
                    $insumo = $row['NombreInsumo'];
                    $costo = $row['CostoTotal'];

                    if (!isset($tablahardware[$empleadoID])) {
                        $tablahardware[$empleadoID] = [
                            'NombreEmpleado' => $nombre,
                            'NombrePuesto' => $puesto,
                            'TotalPorEmpleado' => 0
                        ];
                    }

                    $tablahardware[$empleadoID][$insumo] = $costo;
                    $tablahardware[$empleadoID]['TotalPorEmpleado'] += $costo;
                
                    $columnashardware[$insumo] = true;

                    if (!isset($totaleshardware[$insumo])) {
                        $totaleshardware[$insumo] = 0;
                    }
                    $totaleshardware[$insumo] += $costo;
                    
                    $granTotalhardware += $costo;
                }



                $tablapresup_otrosinsums = [];
                $columnaspresup_otrosinsums = [];
                $totalespresup_otrosinsums = []; 
                $granTotalpresup_otrosinsums = 0; 

                // Agrupar por empleado e insumo para evitar duplicados
                $datosAgrupadosOtros = [];
                foreach ($presup_otrosinsums as $row) {
                    $key = $row->EmpleadoID . '_' . $row->NombreInsumo;
                    if (!isset($datosAgrupadosOtros[$key])) {
                        $datosAgrupadosOtros[$key] = [
                            'EmpleadoID' => $row->EmpleadoID,
                            'NombreEmpleado' => $row->NombreEmpleado,
                            'NombrePuesto' => $row->NombrePuesto,
                            'NombreInsumo' => $row->NombreInsumo,
                            'CostoTotal' => (int) $row->CostoTotal
                        ];
                    } else {
                        $datosAgrupadosOtros[$key]['CostoTotal'] += (int) $row->CostoTotal;
                    }
                }

                foreach ($datosAgrupadosOtros as $key => $row) {
                    $empleadoID = $row['EmpleadoID'];
                    $nombre = $row['NombreEmpleado'];
                    $puesto = $row['NombrePuesto'];
                    $insumo = $row['NombreInsumo'];
                    $costo = $row['CostoTotal'];

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

                // Agrupar por empleado e insumo para evitar duplicados
                $datosAgrupadosLics = [];
                foreach ($presup_lics as $row) {
                    $key = $row->EmpleadoID . '_' . $row->NombreInsumo;
                    if (!isset($datosAgrupadosLics[$key])) {
                        $datosAgrupadosLics[$key] = [
                            'EmpleadoID' => $row->EmpleadoID,
                            'NombreEmpleado' => $row->NombreEmpleado,
                            'NombrePuesto' => $row->NombrePuesto,
                            'NombreInsumo' => $row->NombreInsumo,
                            'CostoTotal' => (int) $row->CostoTotal
                        ];
                    } else {
                        $datosAgrupadosLics[$key]['CostoTotal'] += (int) $row->CostoTotal;
                    }
                }

                foreach ($datosAgrupadosLics as $key => $row) {
                    $empleadoID = $row['EmpleadoID'];
                    $nombre = $row['NombreEmpleado'];
                    $puesto = $row['NombrePuesto'];
                    $insumo = $row['NombreInsumo'];
                    $costo = $row['CostoTotal'];

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

              
              
                $data = ["title" => $request->tipo == 'mens' ? 'MENSUAL' : 'ANUAL',
                        "dato" => $request->tipo == 'mens' ? 'Mensual' : 'Anual',
                        'datosheader' => $datosheader,
                        'GerenciaTb' => $GerenciaTb[0] ?? '',
                        'tablapresup_otrosinsums' => $tablapresup_otrosinsums,
                        'columnaspresup_otrosinsums' => $columnaspresup_otrosinsums,
                        'totalespresup_otrosinsums' => $totalespresup_otrosinsums,
                        'granTotalpresup_otrosinsums' => $granTotalpresup_otrosinsums,
                        'tablahardware' => $tablahardware,
                        'columnashardware' => $columnashardware,
                        'totaleshardware' => $totaleshardware,
                        'granTotalhardware' => $granTotalhardware,
                        'tablapresup_lics' => $tablapresup_lics,
                        'columnaspresup_lics' => $columnaspresup_lics,
                        'totalespresup_lics' => $totalespresup_lics,
                        'granTotalpresup_lics' => $granTotalpresup_lics,
                        'presup_lics' => [],
                        'presup_acces' => $presup_acces,
                        'presup_datos' => $presup_datos,
                        'presup_gps' => $presup_gps,
                        'presup_cal_pagos' => $presup_cal_pagos
                        ];

                    
                $pdf = PDF::loadView('presupuesto.reporte', $data);
                $pdf->setPaper('A4', 'landscape');
                $pdf->render();
                return $pdf->stream('Reporte_Presupuesto_' . $GerenciaTb[0]->NombreGerencia . '_' . ($request->tipo == 'mens' ? 'Mensual' : 'Anual') . '.pdf');

        
       }else{
                $fileName = 'Reporte_Presupuesto_' . $GerenciaTb[0]->NombreGerencia . '_' . ($request->tipo == 'mens' ? 'Mensual' : 'Anual') . '.xlsx';
                return Excel::download(new ReportExport($request->GerenciaID, $request->tipo), $fileName);
       }
        

    }
}

