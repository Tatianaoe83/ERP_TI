<?php

namespace App\Exports;


use App\Models\Gerencia;
use App\Models\Empleados;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;

use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;




class ReportExport implements FromView, ShouldAutoSize, WithStyles
{

    private $gerencia;
    private $tipo;

    //  Contadores
    public $tablapresup_licsCount;
    public $tablapresup_otrosinsumsCount;
    public $presup_accesCount;
    public $presup_datosCount;
    public $presup_gpsCount;
    public $presup_cal_pagosCount;
    public $presup_hardware;
    public $tablaencbezado;



    public function __construct(int $gerencia, string $tipo)
    {
        $this->gerencia  = $gerencia;
        $this->tipo = $tipo;
    }



    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): View
    {

        $numerogerencia = (int) $this->gerencia;

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
            ->groupBy('gerencia.GerenciaID', 'gerencia.NombreGerencia', 'gerencia.NombreGerente')
            ->get();


        $datosheader = $this->tipo == 'mens' ? DB::select('call sp_ReporteCostosPorGerenciaID(?)', [$numerogerencia]) : DB::select('call sp_ReporteCostosAnualesPorGerenciaID(?)', [$numerogerencia]);

        $total = 0;


        foreach ($datosheader as $registro) {

            if (is_numeric($registro->TotalCosto)) {
                $total += $registro->TotalCosto;
            }
        }

        $datosheader[] = (object) [
            'Categoria' => 'Total presupuestado',
            'TotalCosto' => $total
        ];

        $presup_hardware  = $this->tipo == 'mens' ? DB::select('call sp_GenerarReporteHardwarePorGerencia(?)', [$numerogerencia]) : DB::select('call sp_GenerarReporteHardwarePorGerenciaAnual(?)', [$numerogerencia]);
        $presup_otrosinsums = $this->tipo == 'mens' ? DB::select('call sp_GenerarReporteAccesoriosYMantenimientosPorGerencia(?)', [$numerogerencia]) : DB::select('call sp_GenerarReporteAccesoriosYMantenimientosPorGerenciaAnual(?)', [$numerogerencia]);
        $presup_acces =  $this->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasVozPorGerencia(?)', [$numerogerencia]) : DB::select('call sp_ReportePresupuestoLineasVozPorGerenciaAnual(?)', [$numerogerencia]);
        $presup_datos = $this->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasDatosPorGerencia(?)', [$numerogerencia]) : DB::select('call sp_ReportePresupuestoLineasDatosPorGerenciaAnual(?)', [$numerogerencia]);
        $presup_gps = $this->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasGPSPorGerencia(?)', [$numerogerencia]) : DB::select('call sp_ReportePresupuestoLineasGPSPorGerenciaAnual(?)', [$numerogerencia]);
        $presup_lics  = $this->tipo == 'mens' ? DB::select('call sp_GenerarReporteLicenciasPorGerencia(?)', [$numerogerencia]) : DB::select('call sp_GenerarReporteLicenciasPorGerenciaAnual(?)', [$numerogerencia]);


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

        // Recorrer los datos y sumar los valores por cada mes
        foreach ($presup_cal_pagos as $registro) {
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
            'Orden' => 7 // Si deseas agregar algún valor en "Orden" para el total, puedes hacerlo.
        ];


        $tablahardware = [];
        $columnashardware = [];
        $totaleshardware = [];
        $granTotalhardware = 0;

        foreach ($presup_hardware as $row) {
            $empleadoID = $row->EmpleadoID;
            $nombre = $row->NombreEmpleado;
            $puesto = $row->NombrePuesto;
            $insumo = $row->NombreInsumo;
            $costo = (int) $row->CostoTotal;

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

        foreach ($presup_otrosinsums as $row) {
            $empleadoID = $row->EmpleadoID;
            $nombre = $row->NombreEmpleado;
            $puesto = $row->NombrePuesto;
            $insumo = $row->NombreInsumo;
            $costo = (int) $row->CostoTotal;

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
            $costo = (int) $row->CostoTotal;

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



        // Contadores de registros
        $this->tablapresup_licsCount = count($tablapresup_lics);
        $this->tablapresup_otrosinsumsCount = count($tablapresup_otrosinsums);
        $this->presup_accesCount = count($presup_acces);
        $this->presup_datosCount = count($presup_datos);
        $this->presup_gpsCount = count($presup_gps);
        $this->presup_cal_pagosCount = count($presup_cal_pagos);
        $this->tablaencbezadoCount = count($datosheader);
        $this->presup_hardware = count($presup_hardware);


        $data = [
            "title" => $this->tipo == 'mens' ? 'MENSUAL' : 'ANUAL',
            "dato" => $this->tipo == 'mens' ? 'Mensual' : 'Anual',
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


        // Diseño

        return view('presupuesto.reporteExcel', $data);
    }


    public function styles($sheet)
    {


        $tituloLicenciamiento = $this->tablaencbezadoCount + 6;

        $encabezadoicenciamiento = $tituloLicenciamiento + 1;
        $totalLicenciamiento = $encabezadoicenciamiento + $this->tablapresup_licsCount + 1;

        $titulohardware = $totalLicenciamiento + 2;
        $encabezadohardware = $titulohardware + 1;
        $totalhardware = $encabezadohardware + $this->presup_hardware + 1;

        $tituloAccesorios = $totalhardware + 2;
        $encabezadoAccesorios = $tituloAccesorios + 1;
        $totalAccesorios = $encabezadoAccesorios + $this->tablapresup_otrosinsumsCount + 1;

        $tituloTelefonia = $totalAccesorios + 2;
        $encabezadoTelefonia = $tituloTelefonia + 1;
        $totalTelefonia = $encabezadoTelefonia + $this->presup_accesCount;

        $tituloDatos = $totalTelefonia + 2;
        $encabezadoDatos = $tituloDatos + 1;
        $totalDatos = $encabezadoDatos + $this->presup_datosCount;

        $tituloGps = $totalDatos + 2;
        $encabezadoGps = $tituloGps + 1;
        $totalGps = $encabezadoGps + $this->presup_gpsCount;

        $tituloCalendario = $totalGps + 2;
        $encabezadoCalendario = $tituloCalendario + 1;
        $totalCalendario = $encabezadoCalendario + $this->presup_cal_pagosCount;



        // IMAGEN
        $imagePath = public_path('img/logo.png');

        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo de la empresa');
        $drawing->setPath($imagePath);

        $drawing->setCoordinates('d1');
        $drawing->setWidth(80);
        $drawing->setHeight(80);
        $drawing->setWorksheet($sheet);

        // DATOS GENERALES
        $sheet->getStyle("A1:K15")->applyFromArray([
            'font' => [
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF'],
            ]
        ]);
        // TABLA LICENCIAMENTO
        $sheet->getStyle("A{$tituloLicenciamiento}:M{$tituloLicenciamiento}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'c0c0c0'],
            ]
        ]);
        $sheet->getStyle("A{$encabezadoicenciamiento}:M{$encabezadoicenciamiento}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '191970'],
            ]
        ]);

        // TOTALES
        $sheet->getStyle("A{$totalLicenciamiento}:M{$totalLicenciamiento}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'add8e6'],
            ]
        ]);

        // TABLA INVERSIONES
        $sheet->getStyle("A{$titulohardware}:M{$titulohardware}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'c0c0c0'],
            ]
        ]);
        $sheet->getStyle("A{$encabezadohardware}:M{$encabezadohardware}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '191970'],
            ]
        ]);

        // TOTALES
        $sheet->getStyle("A{$totalhardware}:M{$totalhardware}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'add8e6'],
            ]
        ]);


        // TABLA ACCESORIOS
        // TITULO
        $sheet->getStyle("A{$tituloAccesorios}:E{$tituloAccesorios}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'c0c0c0'],
            ]
        ]);

        // ENCABEZADOS
        $sheet->getStyle("A{$encabezadoAccesorios}:E{$encabezadoAccesorios}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '191970'],
            ]
        ]);

        // TOTALES
        $sheet->getStyle("A{$totalAccesorios}:E{$totalAccesorios}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'add8e6'],
            ]
        ]);

        // TABLA TELEFONIA
        // TITULO
        $sheet->getStyle("A{$tituloTelefonia}:E{$tituloTelefonia}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'c0c0c0'],
            ]
        ]);

        // ENCABEZADOS
        $sheet->getStyle("A{$encabezadoTelefonia}:E{$encabezadoTelefonia}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '191970'],
            ]
        ]);

        // TOTALES
        $sheet->getStyle("A{$totalTelefonia}:E{$totalTelefonia}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'add8e6'],
            ]
        ]);

        // TABLA DATOS
        // TITULO
        $sheet->getStyle("A{$tituloDatos}:E{$tituloDatos}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'c0c0c0'],
            ]
        ]);
        $sheet->getStyle("A{$encabezadoDatos}:E{$encabezadoDatos}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '191970'],
            ]
        ]);

        // TOTALES
        $sheet->getStyle("A{$totalDatos}:E{$totalDatos}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'add8e6'],
            ]
        ]);
        // TABLA GPS
        // TITULO
        $sheet->getStyle("A{$tituloGps}:E{$tituloGps}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'c0c0c0'],
            ]
        ]);
        $sheet->getStyle("A{$encabezadoGps}:E{$encabezadoGps}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '191970'],
            ]
        ]);

        // TOTALES
        $sheet->getStyle("A{$totalGps}:E{$totalGps}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'add8e6'],
            ]
        ]);

        // TABLA CALENDARIO DE PAGOS
        // TITULO
        $sheet->getStyle("A{$tituloCalendario}:M{$tituloCalendario}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'c0c0c0'],
            ]
        ]);
        $sheet->getStyle("A{$encabezadoCalendario}:M{$encabezadoCalendario}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '191970'],
            ]
        ]);

        // TOTALES
        $sheet->getStyle("A{$totalCalendario}:M{$totalCalendario}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['argb' => '030404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'add8e6'],
            ]
        ]);
    }




    public function title(): string
    {
        return 'Facturados';
    }
}
