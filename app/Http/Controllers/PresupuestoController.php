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
        ->whereIn('empleados.tipo_persona', ['FISICA', 'REFERENCIADO'])
        ->groupBy('gerencia.GerenciaID', 'gerencia.NombreGerencia', 'gerencia.NombreGerente')
        ->get();

       if ($request->submitbutton == 'pdf'){


                // Calcular totales directamente desde las tablas para verificar
                $employeeQuery = DB::table('empleados as e')
                    ->join('puestos as p', 'e.PuestoID', '=', 'p.PuestoID')
                    ->join('departamentos as d', 'p.DepartamentoID', '=', 'd.DepartamentoID')
                    ->where('d.GerenciaID', $numerogerencia)
                    ->where('e.Estado', 1)
                    ->whereIn('e.tipo_persona', ['FISICA', 'REFERENCIADO'])
                    ->whereNull('e.deleted_at');
        
                $employeeIds = (clone $employeeQuery)->pluck('e.EmpleadoID')->toArray();
                $employeeNames = (clone $employeeQuery)->pluck('e.NombreEmpleado')->toArray();

                $presup_hardware  = $request->tipo == 'mens' ? DB::select('call sp_GenerarReporteHardwarePorGerencia(?)',[$numerogerencia]) : DB::select('call sp_GenerarReporteHardwarePorGerenciaAnual(?)',[$numerogerencia]);
                $presup_otrosinsums = $request->tipo == 'mens' ? DB::select('call sp_GenerarReporteAccesoriosYMantenimientosPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_GenerarReporteAccesoriosYMantenimientosPorGerenciaAnual(?)',[$numerogerencia]);
                $presup_lics  = $request->tipo == 'mens' ? DB::select('call sp_GenerarReporteLicenciasPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_GenerarReporteLicenciasPorGerenciaAnual(?)',[$numerogerencia]);
                $presup_acces =  $request->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasVozPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_ReportePresupuestoLineasVozPorGerenciaAnual(?)',[$numerogerencia]);
                $presup_datos = $request->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasDatosPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_ReportePresupuestoLineasDatosPorGerenciaAnual(?)',[$numerogerencia]);
                $presup_gps = $request->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasGPSPorGerencia(?)',[$numerogerencia]) : DB::select('call sp_ReportePresupuestoLineasGPSPorGerenciaAnual(?)',[$numerogerencia]);

                // Filtrar por los empleados correspondientes
                $presup_hardware = array_values(array_filter($presup_hardware, function($row) use ($employeeIds, $employeeNames) {
                    if (isset($row->EmpleadoID)) {
                        return in_array($row->EmpleadoID, $employeeIds);
                    }
                    return in_array($row->NombreEmpleado, $employeeNames);
                }));
                $presup_otrosinsums = array_values(array_filter($presup_otrosinsums, function($row) use ($employeeIds, $employeeNames) {
                    if (isset($row->EmpleadoID)) {
                        return in_array($row->EmpleadoID, $employeeIds);
                    }
                    return in_array($row->NombreEmpleado, $employeeNames);
                }));
                $presup_lics = array_values(array_filter($presup_lics, function($row) use ($employeeIds, $employeeNames) {
                    if (isset($row->EmpleadoID)) {
                        return in_array($row->EmpleadoID, $employeeIds);
                    }
                    return in_array($row->NombreEmpleado, $employeeNames);
                }));
                $presup_acces = array_values(array_filter($presup_acces, function($row) use ($employeeIds, $employeeNames) {
                    if (isset($row->EmpleadoID)) {
                        return in_array($row->EmpleadoID, $employeeIds);
                    }
                    return in_array($row->NombreEmpleado, $employeeNames);
                }));
                $presup_datos = array_values(array_filter($presup_datos, function($row) use ($employeeIds, $employeeNames) {
                    if (isset($row->EmpleadoID)) {
                        return in_array($row->EmpleadoID, $employeeIds);
                    }
                    return in_array($row->NombreEmpleado, $employeeNames);
                }));
                $presup_gps = array_values(array_filter($presup_gps, function($row) use ($employeeIds, $employeeNames) {
                    if (isset($row->EmpleadoID)) {
                        return in_array($row->EmpleadoID, $employeeIds);
                    }
                    return in_array($row->NombreEmpleado, $employeeNames);
                }));
                
                
                $tipoPersonaFilter = " AND e.tipo_persona IN ('FISICA', 'REFERENCIADO') ";
                $query_params = [$numerogerencia];

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
                WHERE g.GerenciaID = ? " . $tipoPersonaFilter . "
                    AND ii.CateogoriaInsumo = 'RENTA DE IMPRESORA'
                    ", $query_params);

                    $presup_internet_fijo = DB::select("
                    SELECT 
            'Costo Internet Fijo' AS Categoria,
             ROUND(SUM(DISTINCT IFNULL(ii.CostoMensual, 0)), 0) AS CostoTotal
            FROM inventarioinsumo ii
            INNER JOIN empleados e ON ii.EmpleadoID = e.EmpleadoID
            INNER JOIN puestos p ON e.PuestoID = p.PuestoID
            INNER JOIN departamentos d ON p.DepartamentoID = d.DepartamentoID
            INNER JOIN gerencia g ON d.GerenciaID = g.GerenciaID
            WHERE g.GerenciaID = ? " . $tipoPersonaFilter . "
                AND ii.CateogoriaInsumo = 'INTERNET'
                ", $query_params);

                    
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
                                WHERE g.GerenciaID = ? " . $tipoPersonaFilter . "
                                    AND ii.CateogoriaInsumo = 'RENTA DE IMPRESORA'
                                GROUP BY ii.EmpleadoID, ii.NombreInsumo, ii.NumSerie, ii.CostoAnual
                            ) as impresoras_unicas
                    ", $query_params);

                    $presup_internet_fijo = DB::select("
                    SELECT 
                   'Costo Internet Fijo' AS Categoria,
                   ROUND(SUM(DISTINCT IFNULL(ii.CostoAnual, 0)), 0) AS CostoTotal
                   FROM inventarioinsumo ii
                   INNER JOIN empleados e ON ii.EmpleadoID = e.EmpleadoID
                   INNER JOIN puestos p ON e.PuestoID = p.PuestoID
                   INNER JOIN departamentos d ON p.DepartamentoID = d.DepartamentoID
                   INNER JOIN gerencia g ON d.GerenciaID = g.GerenciaID
                   WHERE g.GerenciaID = ? " . $tipoPersonaFilter . "
                       AND ii.CateogoriaInsumo = 'INTERNET'
            ", $query_params);
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
            
                $presup_cal_pagos = $this->obtenerInsumosAnualesFiltrados($numerogerencia);

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
                $fecha_renovacion= [];
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
                        'GerenciaTb' => $GerenciaTb->first() ?? '',
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
                $gerencia = $GerenciaTb->first();
                $nombreGerencia = $gerencia ? $gerencia->NombreGerencia : 'Sin_Gerencia';
                return $pdf->stream('Reporte_Presupuesto_' . $nombreGerencia . '_' . ($request->tipo == 'mens' ? 'Mensual' : 'Anual') . '.pdf');

        
       }else{
                $gerencia = $GerenciaTb->first();
                $nombreGerencia = $gerencia ? $gerencia->NombreGerencia : 'Sin_Gerencia';
                $fileName = 'Reporte_Presupuesto_' . $nombreGerencia . '_' . ($request->tipo == 'mens' ? 'Mensual' : 'Anual') . '.xlsx';
                return Excel::download(new ReportExport($request->GerenciaID, $request->tipo), $fileName);
       }
        
    }

public function verificarFechas(Request $request)
{
    try {
        $gerenciaId = $request->GerenciaID;

        // Año siguiente
        $anioSiguiente = now()->year + 1;

        // 1. CONTEO DE INSUMOS MENSUALES FALTANTES
        $insumosSinMesPagoMensual = DB::table('inventarioinsumo as ii')
            ->join('empleados as e', 'ii.EmpleadoID', '=', 'e.EmpleadoID')
            ->join('puestos as p', 'e.PuestoID', '=', 'p.PuestoID')
            ->join('departamentos as d', 'p.DepartamentoID', '=', 'd.DepartamentoID')
            ->where('d.GerenciaID', $gerenciaId)
            ->where('e.Estado', 1)
            ->whereNull('e.deleted_at')
            ->whereNotNull('ii.FechaRenovacion')
            ->whereNotIn('ii.FechaRenovacion', ['', '0000-00-00', 'Sin asignar', 'Sin asigna'])

            // NUEVO FILTRO
            ->whereYear('ii.FechaRenovacion', $anioSiguiente)

            ->where('ii.FrecuenciaDePago', 'LIKE', '%ensual%')
            ->where(function ($qq) {
                $qq->whereNull('ii.MesDePago')
                   ->orWhere('ii.MesDePago', '')
                   ->orWhere('ii.MesDePago', 'N/A');
            })
            ->count();

        // 2. CONTEO DE INSUMOS ANUALES FALTANTES
        $insumosSinMesPagoAnual = DB::table('inventarioinsumo as ii')
            ->join('empleados as e', 'ii.EmpleadoID', '=', 'e.EmpleadoID')
            ->join('puestos as p', 'e.PuestoID', '=', 'p.PuestoID')
            ->join('departamentos as d', 'p.DepartamentoID', '=', 'd.DepartamentoID')
            ->where('d.GerenciaID', $gerenciaId)
            ->where('e.Estado', 1)
            ->whereNull('e.deleted_at')
            ->whereNotNull('ii.FechaRenovacion')
            ->whereNotIn('ii.FechaRenovacion', ['', '0000-00-00', 'Sin asignar', 'Sin asigna'])

            // NUEVO FILTRO
            ->whereRaw('YEAR(DATE_ADD(ii.FechaRenovacion, INTERVAL 1 YEAR)) = ?', [$anioSiguiente])

            ->where('ii.FrecuenciaDePago', 'LIKE', '%nual%')
            ->where(function ($qq) {
                $qq->whereNull('ii.MesDePago')
                   ->orWhere('ii.MesDePago', '')
                   ->orWhere('ii.MesDePago', 'N/A');
            })
            ->count();

        // 3. TOTAL DE EMPLEADOS
        $totalEmpleados = DB::table('empleados as e')
            ->join('puestos as p', 'e.PuestoID', '=', 'p.PuestoID')
            ->join('departamentos as d', 'p.DepartamentoID', '=', 'd.DepartamentoID')
            ->where('d.GerenciaID', $gerenciaId)
            ->where('e.Estado', 1)
            ->whereNull('e.deleted_at')
            ->distinct()
            ->count('e.EmpleadoID');

        // 4. LÍNEAS HUÉRFANAS
        $lineasOrfanas = DB::table('lineastelefonicas as l')
            ->whereNull('l.deleted_at')
            ->whereNotNull('l.FechaRenovacion')
            ->whereNotIn('l.FechaRenovacion', ['', '0000-00-00', 'Sin asignar', 'Sin asigna'])

             // FILTRO DE ESTADOS
            ->where('l.Disponible', '1')
            ->where('l.Activo', '1')

            // NUEVO FILTRO (Trae tanto las que ya son 2027 como las de 2026 que proyectan a 2027)
            ->where(function($query) use ($anioSiguiente) {
                $query->whereYear('l.FechaRenovacion', $anioSiguiente)
                      ->orWhereRaw('YEAR(DATE_ADD(l.FechaRenovacion, INTERVAL 1 YEAR)) = ?', [$anioSiguiente]);
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                ->from('inventariolineas as il')
                ->whereColumn('il.LineaID', 'l.LineaID')
                ->whereNull('il.deleted_at');
            })
            ->count();

        // 5. INSUMOS HUÉRFANOS
        $insumosOrfanos = DB::table('insumos as i')
            ->whereNotNull('i.FechaRenovacion')
            ->whereNotIn('i.FechaRenovacion', ['', '0000-00-00', 'Sin asignar', 'Sin asigna'])

            // NUEVO FILTRO (Trae tanto los que ya son del siguiente año como los de este año que proyectan al siguiente)
            ->where(function($query) use ($anioSiguiente) {
                $query->whereYear('i.FechaRenovacion', $anioSiguiente)
                      ->orWhereRaw('YEAR(DATE_ADD(i.FechaRenovacion, INTERVAL 1 YEAR)) = ?', [$anioSiguiente]);
            })

            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('inventarioinsumo as ii')
                    ->whereColumn('ii.InsumoID', 'i.ID')
                    ->whereNull('ii.deleted_at')
                    ->where('ii.EmpleadoID', '>', 0);
            })
            ->count();

        return response()->json([
            'success' => true,
            'totalEmpleados' => $totalEmpleados,
            'empleadosSinMesPagoMensual' => $insumosSinMesPagoMensual,
            'empleadosSinMesPagoAnual' => $insumosSinMesPagoAnual,
            'lineasSinAsignarConFecha' => $lineasOrfanas,
            'insumosSinAsignarConFecha' => $insumosOrfanos
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

    private function obtenerInsumosAnualesFiltrados($gerenciaId, $tipoPersona = null)
    {
        $tipoPersonaFilter = " AND e.tipo_persona IN ('FISICA', 'REFERENCIADO') ";
        $bindings = ['gerenciaId' => $gerenciaId];

        // 1. Obtener costos de Windows 10 Pro
        $sqlWin10 = "SELECT 
            CASE 
                WHEN :gerenciaId IN (17, 18) THEN 0.00 
                 ELSE ROUND(IFNULL(MAX(CostoMensual * 1.07), 0)) 
            END AS CostoWindows10Pro
        FROM inventarioinsumo
        WHERE NombreInsumo = 'WINDOWS 10 PRO'";
        $costoWindows10ProObj = DB::selectOne($sqlWin10, ['gerenciaId' => $gerenciaId]);
        $costoWindows10Pro = $costoWindows10ProObj ? $costoWindows10ProObj->CostoWindows10Pro : 0;

        // 2. Obtener costos de Windows 11 Pro
        $sqlWin11 = "SELECT 
            CASE 
                WHEN :gerenciaId IN (17, 18) THEN 0.00 
                 ELSE ROUND(IFNULL(MAX(CostoAnual * 1.07), 0)) 
            END AS CostoWindows11Pro
        FROM inventarioinsumo
        WHERE NombreInsumo = 'WINDOWS 11 PRO'";
        $costoWindows11ProObj = DB::selectOne($sqlWin11, ['gerenciaId' => $gerenciaId]);
        $costoWindows11Pro = $costoWindows11ProObj ? $costoWindows11ProObj->CostoWindows11Pro : 0;

        // 3. Obtener la suma de la MontoRenovacionFianza
        $sqlFianzas = "SELECT SUM(il.MontoRenovacionFianza) as TotalRenovacionFianzas
        FROM inventariolineas il
        INNER JOIN empleados e ON il.EmpleadoID = e.EmpleadoID
        INNER JOIN puestos p ON e.PuestoID = p.PuestoID
        INNER JOIN departamentos d ON p.DepartamentoID = d.DepartamentoID
        INNER JOIN gerencia g ON d.GerenciaID = g.GerenciaID
        WHERE il.MontoRenovacionFianza IS NOT NULL
        AND g.GerenciaID = :gerenciaId" . $tipoPersonaFilter;
        $totalFianzasObj = DB::selectOne($sqlFianzas, $bindings);
        $totalRenovacionFianzas = $totalFianzasObj ? ($totalFianzasObj->TotalRenovacionFianzas ?? 0) : 0;

        $reporteTemp = [];

        // Query 1: Lineas Renta (Orden 5)
        $sqlLineasRenta = "SELECT 
                CONCAT(il.Compania, ' ', il.TipoLinea) AS NombreInsumo,
                SUM((il.CostoRentaMensual)) AS Enero,
                SUM((il.CostoRentaMensual)) AS Febrero,
                SUM((il.CostoRentaMensual)) AS Marzo,
                SUM((il.CostoRentaMensual)) AS Abril,
                SUM((il.CostoRentaMensual)) AS Mayo,
                SUM((il.CostoRentaMensual)) AS Junio,
                SUM((il.CostoRentaMensual)) AS Julio,
                SUM((il.CostoRentaMensual)) AS Agosto,
                SUM((il.CostoRentaMensual)) AS Septiembre,
                SUM((il.CostoRentaMensual)) AS Octubre,
                SUM((il.CostoRentaMensual)) AS Noviembre,
                SUM((il.CostoRentaMensual)) AS Diciembre,
                5 AS Orden  
            FROM 
                inventariolineas il
            INNER JOIN 
                empleados e ON il.EmpleadoID = e.EmpleadoID
            INNER JOIN 
                puestos p ON e.PuestoID = p.PuestoID
            INNER JOIN 
                departamentos d ON p.DepartamentoID = d.DepartamentoID
            INNER JOIN 
                gerencia g ON d.GerenciaID = g.GerenciaID
            WHERE 
                g.GerenciaID = :gerenciaId" . $tipoPersonaFilter . "
            GROUP BY il.Compania, il.TipoLinea
            HAVING (SUM((il.CostoRentaMensual)) * 12) > 0";
        $res1 = DB::select($sqlLineasRenta, $bindings);
        $reporteTemp = array_merge($reporteTemp, $res1);

        // Query 2: Lineas Fianza (Orden 4)
        $sqlLineasFianza = "SELECT 
                CONCAT(il.Compania, ' FIANZA - ', il.TipoLinea) AS NombreInsumo,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 1 THEN (il.CostoFianza) ELSE 0 END) AS Enero,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 2 THEN (il.CostoFianza) ELSE 0 END) AS Febrero,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 3 THEN (il.CostoFianza) ELSE 0 END) AS Marzo,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 4 THEN (il.CostoFianza) ELSE 0 END) AS Abril,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 5 THEN (il.CostoFianza) ELSE 0 END) AS Mayo,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 6 THEN (il.CostoFianza) ELSE 0 END) AS Junio,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 7 THEN (il.CostoFianza) ELSE 0 END) AS Julio,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 8 THEN (il.CostoFianza) ELSE 0 END) AS Agosto,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 9 THEN (il.CostoFianza) ELSE 0 END) AS Septiembre,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 10 THEN (il.CostoFianza) ELSE 0 END) AS Octubre,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 11 THEN (il.CostoFianza) ELSE 0 END) AS Noviembre,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 12 THEN (il.CostoFianza) ELSE 0 END) AS Diciembre,
                 4 AS Orden  
            FROM 
                inventariolineas il
            INNER JOIN 
                empleados e ON il.EmpleadoID = e.EmpleadoID
            INNER JOIN 
                puestos p ON e.PuestoID = p.PuestoID
            INNER JOIN 
                departamentos d ON p.DepartamentoID = d.DepartamentoID
            INNER JOIN 
                gerencia g ON d.GerenciaID = g.GerenciaID
            WHERE 
                il.TipoLinea IN ('Voz', 'Datos', 'GPS')  
                AND g.GerenciaID = :gerenciaId" . $tipoPersonaFilter . "
            GROUP BY il.Compania, il.TipoLinea
            HAVING (
                SUM(CASE WHEN MONTH(il.FechaFianza) = 1 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 2 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 3 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 4 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 5 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 6 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 7 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 8 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 9 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 10 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 11 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 12 THEN (il.CostoFianza) ELSE 0 END)
            ) > 0";
        $res2 = DB::select($sqlLineasFianza, $bindings);
        $reporteTemp = array_merge($reporteTemp, $res2);

        // Query 3: Inversiones (Orden 6)
        $sqlInversiones = "SELECT 
            'INVERSIONES' AS NombreInsumo,
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Enero' THEN i.CostoAnual ELSE 0 END), 0) AS Enero,
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Febrero' THEN i.CostoAnual ELSE 0 END), 0) AS Febrero,
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Marzo' THEN i.CostoAnual ELSE 0 END), 0) AS Marzo,
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Abril' THEN i.CostoAnual ELSE 0 END), 0) AS Abril,
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Mayo' THEN i.CostoAnual ELSE 0 END), 0) AS Mayo,
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Junio' THEN i.CostoAnual ELSE 0 END), 0) + :totalRenovacionFianzas AS Junio,
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Julio' THEN i.CostoAnual ELSE 0 END), 0) AS Julio,
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Agosto' THEN i.CostoAnual ELSE 0 END), 0) AS Agosto,
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Septiembre' THEN i.CostoAnual ELSE 0 END), 0) AS Septiembre,
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Octubre' THEN i.CostoAnual ELSE 0 END), 0) AS Octubre,
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Noviembre' THEN i.CostoAnual ELSE 0 END), 0) AS Noviembre,
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Diciembre' THEN i.CostoAnual ELSE 0 END), 0) AS Diciembre,
            6 as Orden
        FROM 
            inventarioinsumo i
        INNER JOIN 
            empleados e ON i.EmpleadoID = e.EmpleadoID
        INNER JOIN 
            puestos p ON e.PuestoID = p.PuestoID
        INNER JOIN 
            departamentos d ON p.DepartamentoID = d.DepartamentoID
        INNER JOIN 
            gerencia g ON d.GerenciaID = g.GerenciaID
        WHERE 
            (i.FrecuenciaDePago = 'Anual' OR i.FrecuenciaDePago = 'Pago único')
            AND i.CateogoriaInsumo IN ('LAPTOP', 'MONITOR', 'NO BREAK', 'TABLET', 'IMPRESORA') 
            AND g.GerenciaID = :gerenciaId" . $tipoPersonaFilter . "
        HAVING (
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Enero' THEN i.CostoAnual ELSE 0 END), 0) +
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Febrero' THEN i.CostoAnual ELSE 0 END), 0) +
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Marzo' THEN i.CostoAnual ELSE 0 END), 0) +
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Abril' THEN i.CostoAnual ELSE 0 END), 0) +
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Mayo' THEN i.CostoAnual ELSE 0 END), 0) +
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Junio' THEN i.CostoAnual ELSE 0 END), 0) + :totalRenovacionFianzas2 +
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Julio' THEN i.CostoAnual ELSE 0 END), 0) +
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Agosto' THEN i.CostoAnual ELSE 0 END), 0) +
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Septiembre' THEN i.CostoAnual ELSE 0 END), 0) +
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Octubre' THEN i.CostoAnual ELSE 0 END), 0) +
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Noviembre' THEN i.CostoAnual ELSE 0 END), 0) +
            IFNULL(SUM(CASE WHEN i.MesDePago = 'Diciembre' THEN i.CostoAnual ELSE 0 END), 0)
        ) > 0";
        
        $invBindings = array_merge($bindings, [
            'totalRenovacionFianzas' => $totalRenovacionFianzas,
            'totalRenovacionFianzas2' => $totalRenovacionFianzas
        ]);
        $res3 = DB::select($sqlInversiones, $invBindings);
        $reporteTemp = array_merge($reporteTemp, $res3);

        // Query 4: Licencias (Orden 2)
        $sqlLicencias = "SELECT 
        i.NombreInsumo,
        SUM(CASE WHEN i.MesDePago = 'Enero' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro1 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro1 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE i.CostoAnual END ELSE 0 END) AS Enero,
        SUM(CASE WHEN i.MesDePago = 'Febrero' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro2 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro2 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE i.CostoAnual END ELSE 0 END) AS Febrero,
        SUM(CASE WHEN i.MesDePago = 'Marzo' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro3 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro3 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE i.CostoAnual END ELSE 0 END) AS Marzo,
        SUM(CASE WHEN i.MesDePago = 'Abril' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro4 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro4 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE i.CostoAnual END ELSE 0 END) AS Abril,
        SUM(CASE WHEN i.MesDePago = 'Mayo' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro5 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro5 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE i.CostoAnual END ELSE 0 END) AS Mayo,
        SUM(CASE WHEN i.MesDePago = 'Junio' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro6 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro6 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE i.CostoAnual END ELSE 0 END) AS Junio,
        SUM(CASE WHEN i.MesDePago = 'Julio' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro7 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro7 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE i.CostoAnual END ELSE 0 END) AS Julio,
        SUM(CASE WHEN i.MesDePago = 'Agosto' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro8 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro8 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE i.CostoAnual END ELSE 0 END) AS Agosto,
        SUM(CASE WHEN i.MesDePago = 'Septiembre' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro9 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro9 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE i.CostoAnual END ELSE 0 END) AS Septiembre,
        SUM(CASE WHEN i.MesDePago = 'Octubre' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro10 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro10 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE i.CostoAnual END ELSE 0 END) AS Octubre,
        SUM(CASE WHEN i.MesDePago = 'Noviembre' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro11 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro11 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE i.CostoAnual END ELSE 0 END) AS Noviembre,
        SUM(CASE WHEN i.MesDePago = 'Diciembre' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro12 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro12 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE i.CostoAnual END ELSE 0 END) AS Diciembre,
        2 AS Orden
    FROM 
        inventarioinsumo i
    INNER JOIN empleados e ON i.EmpleadoID = e.EmpleadoID
    INNER JOIN puestos p ON e.PuestoID = p.PuestoID
    INNER JOIN departamentos d ON p.DepartamentoID = d.DepartamentoID
    INNER JOIN gerencia g ON d.GerenciaID = g.GerenciaID
    WHERE 
        (i.FrecuenciaDePago = 'Anual' OR i.FrecuenciaDePago = 'Pago único')
        AND i.CateogoriaInsumo = 'LICENCIA'
        AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%')
        AND g.GerenciaID = :gerenciaId" . $tipoPersonaFilter . "
    GROUP BY 
        i.NombreInsumo
    HAVING (
        SUM(CASE WHEN i.MesDePago IN ('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre') AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro13 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro13 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE i.CostoAnual END ELSE 0 END)
    ) > 0";

        $licBindings = array_merge($bindings, [
            'costoWindows10Pro1' => $costoWindows10Pro, 'costoWindows11Pro1' => $costoWindows11Pro,
            'costoWindows10Pro2' => $costoWindows10Pro, 'costoWindows11Pro2' => $costoWindows11Pro,
            'costoWindows10Pro3' => $costoWindows10Pro, 'costoWindows11Pro3' => $costoWindows11Pro,
            'costoWindows10Pro4' => $costoWindows10Pro, 'costoWindows11Pro4' => $costoWindows11Pro,
            'costoWindows10Pro5' => $costoWindows10Pro, 'costoWindows11Pro5' => $costoWindows11Pro,
            'costoWindows10Pro6' => $costoWindows10Pro, 'costoWindows11Pro6' => $costoWindows11Pro,
            'costoWindows10Pro7' => $costoWindows10Pro, 'costoWindows11Pro7' => $costoWindows11Pro,
            'costoWindows10Pro8' => $costoWindows10Pro, 'costoWindows11Pro8' => $costoWindows11Pro,
            'costoWindows10Pro9' => $costoWindows10Pro, 'costoWindows11Pro9' => $costoWindows11Pro,
            'costoWindows10Pro10' => $costoWindows10Pro, 'costoWindows11Pro10' => $costoWindows11Pro,
            'costoWindows10Pro11' => $costoWindows10Pro, 'costoWindows11Pro11' => $costoWindows11Pro,
            'costoWindows10Pro12' => $costoWindows10Pro, 'costoWindows11Pro12' => $costoWindows11Pro,
            'costoWindows10Pro13' => $costoWindows10Pro, 'costoWindows11Pro13' => $costoWindows11Pro,
        ]);
        $res4 = DB::select($sqlLicencias, $licBindings);
        $reporteTemp = array_merge($reporteTemp, $res4);

        // Query 5: Otros Insumos (Orden 3)
        $sqlOtrosInsumos = "SELECT 
            NombreInsumo,
            SUM(Enero) AS Enero,
            SUM(Febrero) AS Febrero,
            SUM(Marzo) AS Marzo,
            SUM(Abril) AS Abril,
            SUM(Mayo) AS Mayo,
            SUM(Junio) AS Junio,
            SUM(Julio) AS Julio,
            SUM(Agosto) AS Agosto,
            SUM(Septiembre) AS Septiembre,
            SUM(Octubre) AS Octubre,
            SUM(Noviembre) AS Noviembre,
            SUM(Diciembre) AS Diciembre,
            3 AS Orden
        FROM (
            SELECT 
                CASE 
                    WHEN i.CateogoriaInsumo = 'REPARACIONES' THEN 'ACCESORIOS Y REFACCIONES'
                    ELSE i.NombreInsumo 
                END AS NombreInsumo,
                CASE WHEN i.MesDePago = 'Enero' THEN i.CostoAnual ELSE 0 END AS Enero,
                CASE WHEN i.MesDePago = 'Febrero' THEN i.CostoAnual ELSE 0 END AS Febrero,
                CASE WHEN i.MesDePago = 'Marzo' THEN i.CostoAnual ELSE 0 END AS Marzo,
                CASE WHEN i.MesDePago = 'Abril' THEN i.CostoAnual ELSE 0 END AS Abril,
                CASE WHEN i.MesDePago = 'Mayo' THEN i.CostoAnual ELSE 0 END AS Mayo,
                CASE WHEN i.MesDePago = 'Junio' THEN i.CostoAnual ELSE 0 END AS Junio,
                CASE WHEN i.MesDePago = 'Julio' THEN i.CostoAnual ELSE 0 END AS Julio,
                CASE WHEN i.MesDePago = 'Agosto' THEN i.CostoAnual ELSE 0 END AS Agosto,
                CASE WHEN i.MesDePago = 'Septiembre' THEN i.CostoAnual ELSE 0 END AS Septiembre,
                CASE WHEN i.MesDePago = 'Octubre' THEN i.CostoAnual ELSE 0 END AS Octubre,
                CASE WHEN i.MesDePago = 'Noviembre' THEN i.CostoAnual ELSE 0 END AS Noviembre,
                CASE WHEN i.MesDePago = 'Diciembre' THEN i.CostoAnual ELSE 0 END AS Diciembre
            FROM 
                inventarioinsumo i
            INNER JOIN empleados e ON i.EmpleadoID = e.EmpleadoID
            INNER JOIN puestos p ON e.PuestoID = p.PuestoID
            INNER JOIN departamentos d ON p.DepartamentoID = d.DepartamentoID
            INNER JOIN gerencia g ON d.GerenciaID = g.GerenciaID
            WHERE 
                (i.FrecuenciaDePago = 'Anual' OR i.FrecuenciaDePago = 'Pago único')
                AND i.CateogoriaInsumo NOT IN ('LAPTOP', 'MONITOR', 'NO BREAK', 'LICENCIA', 'ACCESORIOS', 'BATERIA UPS', 'IMPRESORA') 
                AND g.GerenciaID = :gerenciaId" . $tipoPersonaFilter . "
        ) as sub
        GROUP BY NombreInsumo
        HAVING (
            SUM(Enero) + SUM(Febrero) + SUM(Marzo) + SUM(Abril) + SUM(Mayo) + SUM(Junio) +
            SUM(Julio) + SUM(Agosto) + SUM(Septiembre) + SUM(Octubre) + SUM(Noviembre) + SUM(Diciembre)
        ) > 0";
        $res5 = DB::select($sqlOtrosInsumos, $bindings);
        $reporteTemp = array_merge($reporteTemp, $res5);

        // Query 6: Mensuales (Orden 1)
        $sqlMensuales = "SELECT 
        i.NombreInsumo,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Enero,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Febrero,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Marzo,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Abril,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Mayo,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Junio,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Julio,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Agosto,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Septiembre,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Octubre,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Noviembre,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Diciembre,
        1 AS Orden
    FROM 
        inventarioinsumo i
    INNER JOIN 
        empleados e ON i.EmpleadoID = e.EmpleadoID
    INNER JOIN 
        puestos p ON e.PuestoID = p.PuestoID
    INNER JOIN 
        departamentos d ON p.DepartamentoID = d.DepartamentoID
    INNER JOIN 
        gerencia g ON d.GerenciaID = g.GerenciaID
    WHERE 
        i.FrecuenciaDePago = 'Mensual'
        AND i.CateogoriaInsumo IN ('LICENCIA', 'HOSTING', 'STARLINK', 'INTERNET', 'TABLET')  
        AND g.GerenciaID = :gerenciaId" . $tipoPersonaFilter . "
    GROUP BY 
        i.NombreInsumo
    HAVING 
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) * 12 > 0";
        $res6 = DB::select($sqlMensuales, $bindings);
        $reporteTemp = array_merge($reporteTemp, $res6);

        // Now format Enero to Diciembre as integers/floats without decimals or rounded as MySQL stored procedure does
        foreach ($reporteTemp as $row) {
            $row->Enero = round((float)$row->Enero, 0);
            $row->Febrero = round((float)$row->Febrero, 0);
            $row->Marzo = round((float)$row->Marzo, 0);
            $row->Abril = round((float)$row->Abril, 0);
            $row->Mayo = round((float)$row->Mayo, 0);
            $row->Junio = round((float)$row->Junio, 0);
            $row->Julio = round((float)$row->Julio, 0);
            $row->Agosto = round((float)$row->Agosto, 0);
            $row->Septiembre = round((float)$row->Septiembre, 0);
            $row->Octubre = round((float)$row->Octubre, 0);
            $row->Noviembre = round((float)$row->Noviembre, 0);
            $row->Diciembre = round((float)$row->Diciembre, 0);
        }

        // Sort by Orden
        usort($reporteTemp, function($a, $b) {
            return $a->Orden <=> $b->Orden;
        });

        return $reporteTemp;
    }
}

