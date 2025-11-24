<?php

namespace App\Http\Controllers;

use App\DataTables\ReportesDataTable;
use App\Exports\ReporteExport;
use App\Helpers\JoinHelper;
use App\Helpers\ReporteHelper;
use Illuminate\Http\Request;
use App\Http\Requests\CreateReportesRequest;
use App\Http\Requests\UpdateReportesRequest;
use App\Repositories\ReportesRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\Reportes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Response;
use Stringable;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class ReportesController extends AppBaseController
{
    /** @var ReportesRepository $reportesRepository*/
    private $reportesRepository;

    public function __construct(ReportesRepository $reportesRepo)
    {
        $this->reportesRepository = $reportesRepo;

        $this->middleware('permission:ver-reportes')->only(['index', 'show', 'preview']);
        $this->middleware('permission:crear-reportes')->only(['create', 'store']);
        $this->middleware('permission:editar-reportes')->only(['edit', 'update']);
        $this->middleware('permission:borrar-reportes')->only(['destroy']);
        $this->middleware('permission:exportar-reportes')->only(['exportPdf', 'exportExcel']);
    }

    /**
     * Display a listing of the Reportes.
     *
     * @param ReportesDataTable $reportesDataTable
     *
     * @return Response
     */
    public function index(ReportesDataTable $dataTable)
    {
        if (request()->ajax()) {
            $query = Reportes::select(['id', 'title', 'query_details']);

            return DataTables::of($query)
                ->addColumn('action', function ($row) {
                    return view('reportes.datatables_actions', ['id' => $row->id])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return $dataTable->render('reportes.index');
    }

    /**
     * Show the form for creating a new Reportes.
     *
     * @return Response
     */
    public function create()
    {
        return view('reportes.create');
    }

    /**
     * Store a newly created Reportes in storage.
     *
     * @param CreateReportesRequest $request
     *
     * @return Response
     */
    public function store(CreateReportesRequest $request)
    {
        $input = $request->all();

        $reportes = $this->reportesRepository->create($input);

        Flash::success('Reportes saved successfully.');

        return redirect(route('reportes.index'));
    }

    protected array $relacionesUniversales = [
        'categorias' => [
            'tiposdecategorias' => ['tiposdecategorias.ID', '=', 'categorias.TipoID'],
        ],
        'departamentos' => [
            'gerencia' => ['gerencia.GerenciaID', '=', 'departamentos.GerenciaID'],
        ],
        'empleados' => [
            'obras' => ['obras.ObraID', '=', 'empleados.ObraID'],
            'puestos' => ['puestos.PuestoID', '=', 'empleados.PuestoID'],
            'inventarioinsumo' => ['inventarioinsumo.EmpleadoID', '=', 'empleados.EmpleadoID'],
            'inventarioequipo' => ['inventarioequipo.EmpleadoID', '=', 'empleados.EmpleadoID'],
            'inventariolineas' => ['inventariolineas.EmpleadoID', '=', 'empleados.EmpleadoID']
        ],
        'equipos' => [
            'categorias' => ['categorias.ID', '=', 'equipos.CategoriaID'],
        ],
        'gerencia' => [
            'unidadesdenegocio' => ['unidadesdenegocio.UnidadNegocioID', '=', 'gerencia.UnidadNegocioID'],
        ],
        'insumos' => [
            'categorias' => ['categorias.ID', '=', 'insumos.CategoriaID'],
        ],
        'inventarioequipo' => [
            'empleados' => ['empleados.EmpleadoID', '=', 'inventarioequipo.EmpleadoID'],
        ],
        'inventarioinsumo' => [
            'empleados' => ['empleados.EmpleadoID', '=', 'inventarioinsumo.EmpleadoID'],
            'insumos' => ['insumos.InsumoID', '=', 'inventarioinsumo.InsumoID'],
        ],
        'inventariolineas' => [
            'empleados' => ['empleados.EmpleadoID', '=', 'inventariolineas.EmpleadoID'],
            'lineastelefonicas' => ['lineastelefonicas.LineaID', '=', 'inventariolineas.LineaID'],
            'obras' => ['obras.ObraID', '=', 'inventariolineas.ObraID'],

        ],
        'lineastelefonicas' => [
            'obras' => ['obras.ObraID', '=', 'lineastelefonicas.ObraID'],
            'planes' => ['planes.ID', '=', 'lineastelefonicas.PlanID']
        ],
        'obras' => [
            'unidadesdenegocio' => ['unidadesdenegocio.UnidadNegocioID', '=', 'obras.UnidadNegocioID'],
        ],
        'puestos' => [
            'departamentos' => ['departamentos.DepartamentoID', '=', 'puestos.DepartamentoID'],
        ],
        'planes' => [
            'companiaslineastelefonicas' => ['companiaslineastelefonicas.ID', '=', 'planes.CompaniaID'],
        ],
        'unidadesdenegocio' => [
            'gerencia' => ['gerencia.UnidadNegocioID', '=', 'unidadesdenegocio.UnidadNegocioID'],
        ]
    ];

    /**
     * Display the specified Reportes.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $reportes = $this->reportesRepository->find($id);

        if (empty($reportes)) {
            Flash::error('Reporte no encontrado');
            return redirect(route('reportes.index'));
        }

        $metadata = json_decode($reportes->query_details, true);

        if (!$metadata || !isset($metadata['tabla_principal'])) {
            Log::debug('Pasa aqui2');
            return redirect()->route('reportes.index')
                ->with('error', 'No se pudo interpretar la metadata del reporte.');
        }

        try {
            $metadata = json_decode($reportes->query_details, true);
            $resultado = ReporteHelper::ejecutarConsulta($metadata, $this->relacionesUniversales);
        } catch (\Exception $e) {
            return redirect()->route('reportes.index')
                ->with('error', 'Error al ejecutar la consulta: ' . $e->getMessage());
        }

        if ($resultado->isEmpty()) {
            return redirect()->route('reportes.index')
                ->with('error', 'No se encontraron resultados para el reporte.');
        }


        if (request()->ajax()) {
            return view('reportes.preview', compact('resultado'));
        }

        return view('reportes.show', compact('reportes', 'resultado'));
    }

    /**
     * Show the form for editing the specified Reportes.
     *
     * @param int $id
     *
     * @return Response
     */

    public function edit($id)
    {
        $reportes = $this->reportesRepository->find($id);

        if (empty($reportes)) {
            Flash::error('Reporte no encontrado');
            return redirect(route('reportes.index'));
        }

        $metadata = json_decode($reportes->query_details, true);

        $tablaPrincipal = $metadata['tabla_principal'] ?? null;
        $tablaRelacionInput  = $metadata['tabla_relacion'] ?? null;
        $ordenCol = $metadata['ordenColumna'] ?? null;
        $ordenDir = $metadata['ordenDireccion'] ?? null;
        $limite = $metadata['limite'] ?? null;
        $columnasSeleccionadas = $metadata['columnas'] ?? [];
        $tablaRelacion = is_array($tablaRelacionInput) ? $tablaRelacionInput : [$tablaRelacionInput];

        $condiciones = $metadata['filtros'] ?? [];

        $columnasPrincipales = $tablaPrincipal ? Collect(Schema::getColumnListing($tablaPrincipal))
            ->reject(fn($col) => Str::endsWith($col, ['ID', 'Id', '_id', '_at', 'created_at', 'updated_at', 'deleted_at']))
            ->map(fn($col) => $col)
            ->toArray() : [];
        $columnasRelacion    = [];

        foreach ($tablaRelacion as $tablaRel) {
            if (Schema::hasTable($tablaRel)) {
                $columnasRelacion[$tablaRel] = Collect(Schema::getColumnListing($tablaRel))
                    ->reject(fn($col) => Str::endsWith($col, ['ID', 'Id', '_id', '_at', 'created_at', 'updated_at', 'deleted_at']))
                    ->map(fn($col) => $col)
                    ->toArray();
            }
        }

        return view('reportes.edit', compact(
            'reportes',
            'metadata',
            'tablaPrincipal',
            'tablaRelacion',
            'columnasPrincipales',
            'columnasRelacion',
            'ordenCol',
            'ordenDir',
            'limite',
            'columnasSeleccionadas',
            'condiciones'
        ));
    }

    /**
     * Update the specified Reportes in storage.
     *
     * @param int $id
     * @param UpdateReportesRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateReportesRequest $request)
    {
        $reportes = $this->reportesRepository->find($id);

        if (empty($reportes)) {
            Flash::error('Reporte no encontrado');
            return redirect(route('reportes.index'));
        }

        $columnas = $request->input('columnas', []);
        $filtros = $request->input('filtros', []);
        $orderCol = $request->input('ordenColumna');
        $orderDir = $request->input('ordenDireccion');
        $limite = $request->input('limite');

        $metadataOriginal = json_decode($reportes->query_details, true);

        $nuevoMetadata = array_merge(
            $metadataOriginal,
            [
                'columnas' => $columnas,
                'ordenColumna' => $orderCol,
                'ordenDireccion' => $orderDir,
                'limite' => $limite,
                'filtros' => $filtros
            ]
        );

        $reportes->query_details = json_encode($nuevoMetadata);
        $reportes->save();

        Flash::success('Reporte actualizado correctamente.');
        return redirect(route('reportes.index'));
    }

    /**
     * Remove the specified Reportes from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $reportes = $this->reportesRepository->find($id);

        if (empty($reportes)) {
            Flash::error('Reporte not found');

            return redirect(route('reportes.index'));
        }

        $this->reportesRepository->delete($id);

        Flash::success('Reporte deleted successfully.');

        return redirect(route('reportes.index'));
    }

    public function exportPdf($id)
    {

        set_time_limit(600);
        ini_set('memory_limit', '2048M');

        $reportes = $this->reportesRepository->find($id);

        if (empty($reportes)) {
            Flash::error('Reporte no encontrado');
            return redirect(route('reportes.index'));
        }

        $metadata = json_decode($reportes->query_details, true);

        if (!$metadata || !isset($metadata['tabla_principal'])) {
            return redirect()->route('reportes.index')
                ->with('error', 'No se pudo interpretar la metadata del reporte.');
        }

        try {
            $resultado = ReporteHelper::ejecutarConsulta($metadata, $this->relacionesUniversales);
        } catch (\Exception $e) {
            return redirect()->route('reportes.index')
                ->with('error', 'Error al ejecutar la consulta' . $e->getMessage());
        }

        $columns = collect($resultado)->first() ? collect($resultado->first())->keys()->map(function ($key) {
            return ['title' => $key, 'field' => $key];
        })->values() : [];

        if ($resultado->isEmpty()) {
            return redirect()->route('reportes.index')
                ->with('error', 'No se encontraron resultados para el reporte.');
        }

        $nombre_reporte = $reportes->title;

        $pdf = Pdf::loadView('reportes.exportPdf', compact('reportes', 'resultado', 'columns', 'nombre_reporte'));
        return $pdf->download(Str::slug($reportes->title) . '.pdf');
    }

    public function exportExcel($id)
    {
        $reportes = $this->reportesRepository->find($id);

        if (!$reportes) {
            return redirect()->route('reportes.index')->with('error', 'Reporte no encontrado');
        }

        $metadata = json_decode($reportes->query_details, true);

        try {
            $datos = ReporteHelper::ejecutarConsulta($metadata, $this->relacionesUniversales);
        } catch (\Exception $e) {
            return redirect()->route('reportes.index')
                ->with('error', 'Error al ejecutar la consulta' . $e->getMessage());
        }

        if ($datos->isEmpty()) {
            return redirect()->route('reportes.index')
                ->with('error', 'No se encontraron resultados para el reporte.');
        }

        $columnas = array_keys((array) $datos->first());

        return Excel::download(new ReporteExport($datos, $columnas), Str::slug($reportes->title) . '.xlsx');
    }

    public function preview(Request $request)
    {
        try {
            $metadata = $request->all();

            if (empty($metadata['tabla_principal']) || empty($metadata['columnas'])) {
                return response()->json(['error' => 'Faltan datos esenciales.'], 422);
            }

            if (!is_array($metadata['tabla_relacion'])) {
                $metadata['tabla_relacion'] = json_decode($metadata['tabla_relacion'], true) ?? [];
            }

            $metadata['limite'] = 10;

            $resultado = ReporteHelper::ejecutarConsulta(
                $metadata,
                $this->relacionesUniversales
            );

            $html = view('reportes.preview', compact('resultado'))->render();

            return response()->json(['html' => $html]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error en preview',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ], 500);
        }
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $tabla = $request->get('tabla');
        $columna = $request->get('columna');
        $query = $request->get('query');

        if (!Schema::hasTable($tabla) || !Schema::hasColumn($tabla, $columna)) {
            return response()->json([], 400);
        }

        $resultados = DB::table($tabla)
            ->select($columna)
            ->where($columna, 'like', '%' . $query . '%')
            ->groupBy($columna)
            ->limit(5)
            ->pluck($columna);

        return response()->json($resultados);
    }
}
