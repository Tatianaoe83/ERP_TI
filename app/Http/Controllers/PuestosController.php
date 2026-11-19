<?php

namespace App\Http\Controllers;

use App\DataTables\PuestosDataTable;
use App\Http\Requests;
use App\Http\Requests\CreatePuestosRequest;
use App\Http\Requests\UpdatePuestosRequest;
use App\Repositories\PuestosRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Direccion;
use App\Models\Gerencia;
use App\Models\Puestos;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class PuestosController extends AppBaseController
{
    /** @var PuestosRepository $puestosRepository*/
    private $puestosRepository;

    public function __construct(PuestosRepository $puestosRepo)
    {
        $this->puestosRepository = $puestosRepo;
        $this->middleware('permission:ver-puestos|crear-puestos|editar-puestos|borrar-puestos')->only('index');
        $this->middleware('permission:crear-puestos', ['only' => ['create','store']]);
        $this->middleware('permission:editar-puestos', ['only' => ['edit','update']]);
        $this->middleware('permission:borrar-puestos', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the Puestos.
     *
     * @param PuestosDataTable $puestosDataTable
     *
     * @return Response
     */
    public function index()
    {
        $puestos = Puestos::with(['departamentos.gerencia.direccion'])
            ->when(request('area_id'), function ($query, $areaId) {
                $query->where('DepartamentoID', $areaId);
            })
            ->orderBy('NombrePuesto')
            ->get();

        $this->marcarDireccionVisible($puestos);

        $areas = \App\Models\Departamentos::with('gerencia')->orderBy('NombreDepartamento')->get();

        return view('puestos.index', compact('puestos', 'areas'));
    }

    /**
     * Show the form for creating a new Puestos.
     *
     * @return Response
     */
    public function create()
    {
        return view('puestos.create');
    }

    /**
     * Store a newly created Puestos in storage.
     *
     * @param CreatePuestosRequest $request
     *
     * @return Response
     */
    public function store(CreatePuestosRequest $request)
    {
        $input = $request->all();

        $puestos = $this->puestosRepository->create($input);

        Flash::success('Puesto guardado.');

        return redirect(route('puestos.index'));
    }

    /**
     * Display the specified Puestos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $puestos = $this->puestosRepository->find($id);

        if (empty($puestos)) {
            Flash::error('Puesto no encontrado');

            return redirect(route('puestos.index'));
        }

        return view('puestos.show')->with('puestos', $puestos);
    }

    /**
     * Show the form for editing the specified Puestos.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $puestos = $this->puestosRepository->find($id);

        if (empty($puestos)) {
            Flash::error('Puesto no encontrado');

            return redirect(route('puestos.index'));
        }

        return view('puestos.edit')->with('puestos', $puestos);
    }

    /**
     * Update the specified Puestos in storage.
     *
     * @param int $id
     * @param UpdatePuestosRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatePuestosRequest $request)
    {
        $puestos = $this->puestosRepository->find($id);

        if (empty($puestos)) {
            Flash::error('Puesto no encontrado');

            return redirect(route('puestos.index'));
        }

        $puestos = $this->puestosRepository->update($request->all(), $id);

        Flash::success('Puesto actualizado.');

        return redirect(route('puestos.index'));
    }

    /**
     * Remove the specified Puestos from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $puestos = $this->puestosRepository->find($id);

        if (empty($puestos)) {
            Flash::error('Puesto no encontrado');

            return redirect(route('puestos.index'));
        }

        $this->puestosRepository->delete($id);

        Flash::success('Puesto borrado.');

        return redirect(route('puestos.index'));
    }

    private function marcarDireccionVisible($puestos): void
    {
        $porGerencia = [];
        foreach (Gerencia::with('direccion')->whereNotNull('DireccionID')->orderBy('GerenciaID')->get() as $gerencia) {
            $clave = $this->claveEstructura($gerencia->NombreGerencia);
            if (!isset($porGerencia[$clave]) && $gerencia->direccion) {
                $porGerencia[$clave] = $gerencia->direccion;
            }
        }

        $porNombre = Direccion::query()->get()->keyBy(function ($direccion) {
            return $this->claveEstructura($direccion->NombreDireccion);
        });

        $alias = [
            'DIRECCION JURIDICA' => 'JURIDICA Y DE GESTION ESTRATEGICA',
            'VIAS TERRESTRES ESTRUCTURA' => 'VIAS TERRESTRES',
            'VIAS TERRESTRES OBRA' => 'VIAS TERRESTRES',
            'DESARROLLO DE NEGOCIOS' => 'DESARROLLO DE NEGOCIOS',
            'DIRECCION ADMINISTRACION Y FINANZAS' => 'ADMINISTRACION Y FINANZAS',
            'GASTOS COMPARTIDOS' => 'DIRECCION GENERAL',
            'GASTOS CORPORATIVOS' => 'DIRECCION GENERAL',
            'PROYECTOS' => 'TECNICA CORPORATIVA',
            'EDIFICACION ESTRUCTURA' => 'CONSTRUCCION',
            'EDIFICACION OBRA' => 'CONSTRUCCION',
        ];

        $porUnidad = DB::table('direccion_unidad_negocio')
            ->join('direcciones', 'direcciones.DireccionID', '=', 'direccion_unidad_negocio.DireccionID')
            ->whereNull('direcciones.deleted_at')
            ->get(['direccion_unidad_negocio.UnidadNegocioID', 'direcciones.DireccionID', 'direcciones.NombreDireccion'])
            ->groupBy('UnidadNegocioID');

        foreach ($puestos as $puesto) {
            $gerencia = $puesto->departamentos->gerencia ?? null;
            if (!$gerencia) {
                continue;
            }
            if ($gerencia->direccion) {
                $puesto->direccionVisible = $gerencia->direccion;
                continue;
            }

            $clave = $this->claveEstructura($gerencia->NombreGerencia);
            $direccion = $porGerencia[$clave] ?? null;
            if (!$direccion && isset($alias[$clave])) {
                $direccion = $porNombre->get($alias[$clave]);
            }
            if (!$direccion) {
                $ligadas = $porUnidad->get($gerencia->UnidadNegocioID);
                if ($ligadas && $ligadas->count() === 1) {
                    $direccion = $porNombre->firstWhere('DireccionID', (int) $ligadas->first()->DireccionID);
                }
            }
            $puesto->direccionVisible = $direccion;
        }
    }

    private function claveEstructura(string $nombre): string
    {
        $nombre = mb_strtoupper(trim($nombre));
        $nombre = strtr($nombre, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N']);
        $nombre = preg_replace('/\s+/', ' ', $nombre);

        return str_replace('GERENTE DE ', 'GERENCIA DE ', $nombre);
    }
}
