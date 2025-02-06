<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Gerencia;
use PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;



class PresupuestoController extends Controller
{
   
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {       
        return view('presupuesto.index');
      
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


       if ($request->submitbutton == 'pdf'){
                $GerenciaTb = Gerencia::query()
                ->select("*")
                ->where('GerenciaID','=', $request->GerenciaID)
                ->get();

                $presup_acces =  $request->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasVozPorGerencia(?)',[$request->GerenciaID]) : DB::select('call sp_ReportePresupuestoLineasVozPorGerenciaAnual(?)',[$request->GerenciaID]);
                $presup_datos = $request->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasDatosPorGerencia(?)',[$request->GerenciaID]) : DB::select('call sp_ReportePresupuestoLineasDatosPorGerenciaAnual(?)',[$request->GerenciaID]);
                $presup_gps = $request->tipo == 'mens' ? DB::select('call sp_ReportePresupuestoLineasGPSPorGerencia(?)',[$request->GerenciaID]) : DB::select('call sp_ReportePresupuestoLineasGPSPorGerenciaAnual(?)',[$request->GerenciaID]);
            


                $data = ["title" => $request->tipo == 'mens' ? 'MENSUAL' : 'ANUAL',
                        "dato" => $request->tipo == 'mens' ? 'Mensual' : 'Anual',
                        'GerenciaTb' => $GerenciaTb,
                        'presup_acces' => $presup_acces,
                        'presup_datos' => $presup_datos,
                        'presup_gps' => $presup_gps,
                        ];

                    
                $pdf = PDF::loadView('presupuesto.reporte', $data);
                $pdf->setPaper('A4', 'landscape');
                $pdf->render();
                return $pdf->stream('document.pdf');

        
       }else{
                $fileName = 'Reporte_Presupuesto_' . ($request->tipo == 'mens' ? 'Mensual' : 'Anual') . '.xlsx';
                return Excel::download(new ReportExport($request->GerenciaID, $request->tipo), $fileName);
       }
        

    }
}
