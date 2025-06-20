<?php

namespace App\Http\Controllers;

use App\DataTables\ReportesDataTable;
use App\Exports\ReporteExport;
use App\Helpers\JoinHelper;
use App\Helpers\ReporteHelper;
use App\Http\Requests;
use App\Http\Requests\CreateReportesRequest;
use App\Http\Requests\UpdateReportesRequest;
use App\Repositories\ReportesRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\Reportes;
use Barryvdh\DomPDF\Facade\Pdf;
use GuzzleHttp\Psr7\Request;
use Response;
use Stringable;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class ReportesController extends AppBaseController
{
    /** @var ReportesRepository $reportesRepository*/
    private $reportesRepository;

    public function __construct(ReportesRepository $reportesRepo)
    {
        $this->reportesRepository = $reportesRepo;

        $this->middleware('permission:ver-reportes')->only(['index', 'show']);
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
            'gerencia' => ['gerencia.GerenciaID', '=', 'departamentos.GerencialID'],
        ],
        'empleados' => [
            'obras' => ['obras.ObraID', '=', 'empleados.ObraID'],
            'puestos' => ['puestos.PuestoID', '=', 'empleados.PuestoID'],
            'inventarioinsumo' => ['inventarioinsumo.EmpleadoID', '=', 'inventarioinsumo.EmpleadoID'],
            'inventarioequipo' => ['inventarioequipo.EmpleadoID', '=', 'empleados.EmpleadoID'],
            'inventariolineas' => ['inventariolineas.EmpleadoID', '=', 'empleados.EmpleadoID']
        ],
        'equipos' => [
            'categorias' => ['categorias.CategoriaID', '=', 'equipos.CategoriaID'],
        ],
        'gerencia' => [
            'unidadesdenegocio' => ['unidadesdenegocio.UnidadNegocioID', '=', 'gerencia.UnidadNegocioID'],
        ],
        'gerencias_usuarios' => [
            'gerencia' => ['gerencia.GerenciaID', '=', 'gerencias_usuarios.GerencialID'],
            'users' => ['users.id', '=', 'gerencias_usuarios.user_id'],
        ],
        'insumos' => [
            'categorias' => ['categorias.CategoriaID', '=', 'insumos.CategoriaID'],
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
        ],
        'obras' => [
            'unidadesdenegocio' => ['unidadesdenegocio.UnidadNegocioID', '=', 'obras.UnidadNegocioID'],
        ],
        'puestos' => [
            'departamentos' => ['departamentos.DepartamentoID', '=', 'puestos.DepartamentoID'],
        ],
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

        $metadata = json_decode($reportes->query_metadata, true);

        if (!$metadata || !isset($metadata['tabla_principal'])) {
            return redirect()->route('reportes.index')
                ->with('error', 'No se pudo interpretar la metadata del reporte.');
        }

        $tabla = $metadata['tabla_principal'];
        $relacionesBrutas = $metadata['tabla_relacion'] ?? [];
        $relaciones = is_array($relacionesBrutas) ? $relacionesBrutas : [$relacionesBrutas];
        $columnas = $metadata['columnas'] ?? ['*'];
        $filtros = $metadata['filtros'] ?? [];
        $grupo = $metadata['grupo'] ?? null;
        $ordenCol = $metadata['ordenColumna'] ?? null;
        $ordenDir = $metadata['ordenDireccion'] ?? 'asc';
        $limite = $metadata['limite'] ?? null;

        $query = DB::table($tabla);
        $joinsHechos = [];

        foreach ($relaciones as $relacion) {
            $camino = JoinHelper::resolverRutaJoins($tabla, $relacion, $this->relacionesUniversales);
            foreach ($camino as [$tablaJoin, [$from, $op, $to]]) {
                if (!in_array($tablaJoin, $joinsHechos)) {
                    $query->join($tablaJoin, $from, $op, $to);
                    $joinsHechos[] = $tablaJoin;
                }
            }
        }

        if ($grupo) {
            $columnas = array_map(function ($col) use ($grupo) {
                return $col === $grupo ? $col : DB::raw("MAX($col) as `" . str_replace('.', '_', $col) . "`");
            }, $columnas);
        }

        $query->select($columnas);

        foreach ($filtros as $filtro) {
            if (!empty($filtro['columna']) && isset($filtro['valor'])) {
                $valor = $filtro['valor'];
                if ($filtro['operador'] === 'like') {
                    $valor = '%' . $valor . '%';
                }
                $query->where($filtro['columna'], $filtro['operador'] ?? '=', $valor);
            }
        }

        if ($grupo) {
            $query->groupBy($grupo);
        }

        if ($ordenCol) {
            $query->orderBy($ordenCol, $ordenDir);
        }

        if ($limite) {
            $query->limit($limite);
        }

        try {
            $resultado = $query->get();
        } catch (\Exception $e) {
            return redirect()->route('reportes.index')
                ->with('error', 'Error al ejecutar la consulta: ' . $e->getMessage());
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

        $metadata = json_decode($reportes->query_metadata, true);

        $tablaPrincipal = $metadata['tabla_principal'] ?? null;
        $tablaRelacionInput  = $metadata['tabla_relacion'] ?? null;
        $grupo = $metadata['grupo'] ?? null;
        $ordenCol = $metadata['ordenColumna'] ?? null;
        $ordenDir = $metadata['ordenDireccion'] ?? null;
        $limite = $metadata['limite'] ?? null;
        $columnasSeleccionadas = $metadata['columnas'] ?? [];
        $tablaRelacion = is_array($tablaRelacionInput) ? $tablaRelacionInput : [$tablaRelacionInput];

        $condiciones = $metadata['filtros'] ?? [];

        $columnasPrincipales = $tablaPrincipal ? Schema::getColumnListing($tablaPrincipal) : [];
        $columnasRelacion    = [];

        foreach ($tablaRelacion as $tablaRel) {
            if (Schema::hasTable($tablaRel)) {
                $columnasRelacion[$tablaRel] = Schema::getColumnListing($tablaRel);
            }
        }

        return view('reportes.edit', compact(
            'reportes',
            'metadata',
            'tablaPrincipal',
            'tablaRelacion',
            'columnasPrincipales',
            'columnasRelacion',
            'grupo',
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
        $group = $request->input('grupo');
        $limite = $request->input('limite');

        $metadataOriginal = json_decode($reportes->query_metadata, true);

        $nuevoMetadata = array_merge(
            $metadataOriginal,
            [
                'columnas' => $columnas,
                'ordenColumna' => $orderCol,
                'ordenDireccion' => $orderDir,
                'grupo' => $group,
                'limite' => $limite,
                'filtros' => $filtros
            ]
        );

        $reportes->query_metadata = json_encode($nuevoMetadata);
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
        $reportes = $this->reportesRepository->find($id);

        if (empty($reportes)) {
            Flash::error('Reporte no encontrado');
            return redirect(route('reportes.index'));
        }

        $metadata = json_decode($reportes->query_metadata, true);

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

        $columns = collect($resultado->first() ?? [])->map(function ($_, $key) {
            return ['title' => $key, 'field' => $key];
        })->values();

        $nombre_reporte = $reportes->title;

        $pdf = Pdf::loadView('reportes.exportPdf', compact('reportes', 'resultado', 'columns', 'nombre_reporte'));
        return $pdf->download(Str::slug($reportes->title) . '.pdf');
    }

    public function preview(Request $request)
    {
        $metadata = $request->all();

        if (empty($metadata['tabla_principal']) || empty($metadata['columnas'])) {
            return back()->with('error', 'Informacion insuficiente para previsualizar');
        }

        try {
            $resultado = ReporteHelper::ejecutarConsulta(
                $metadata['tabla_principal'],
                $metadata['tabla_relacion'] ?? [],
                $metadata['columnas'] ?? ['*'],
                $metadata['filtros'] ?? [],
                $metadata['grupo'] ?? null,
                $metadata['ordenColumna'] ?? null,
                $metadata['ordenDireccion'] ?? 'asc',
                $metadata['limite'] ?? null
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar el preview' . $e->getMessage());
        }

        return view('reportes.index', compact('resultado', 'metadata'));
    }

    public function exportExcel($id)
    {
        $reportes = $this->reportesRepository->find($id);

        if (!$reportes) {
            return redirect()->route('reportes.index')->with('error', 'Reporte no encontrado');
        }

        $metadata = json_decode($reportes->query_metadata, true);
        $datos = \App\Helpers\ReporteHelper::ejecutarConsulta($metadata, $this->relacionesUniversales);

        return Excel::download(new ReporteExport($datos), Str::slug($reportes->title) . '.xlsx');
    }
}
