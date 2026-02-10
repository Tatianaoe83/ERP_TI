<?php

namespace App\Http\Controllers;

use App\DataTables\DepartamentosDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateDepartamentosRequest;
use App\Http\Requests\UpdateDepartamentosRequest;
use App\Repositories\DepartamentosRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\DepartamentoRequerimientos;
use Response;
use App\Models\Departamentos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Normalizer;
use Yajra\DataTables\DataTables;

class DepartamentosController extends AppBaseController
{
    /** @var DepartamentosRepository $departamentosRepository*/
    private $departamentosRepository;

    public function __construct(DepartamentosRepository $departamentosRepo)
    {
        $this->departamentosRepository = $departamentosRepo;

        $this->middleware('permission:ver-departamentos|crear-departamentos|editar-departamentos|borrar-departamentos')->only('index');
        $this->middleware('permission:crear-departamentos', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-departamentos', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-departamentos', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the Departamentos.
     *
     * @param DepartamentosDataTable $departamentosDataTable
     *
     * @return Response
     */
    public function index(DepartamentosDataTable $departamentosDataTable)
    {
        if (request()->ajax()) {
            $unidades = Departamentos::join('gerencia', 'departamentos.GerenciaID', '=', 'gerencia.GerenciaID')
                ->select([
                    'departamentos.DepartamentoID',
                    'departamentos.NombreDepartamento',
                    'gerencia.NombreGerencia as nombre_gerencia'
                ]);


            return DataTables::of($unidades)
                ->addColumn('action', function ($row) {
                    return view('departamentos.datatables_actions', ['id' => $row->DepartamentoID])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return $departamentosDataTable->render('departamentos.index');
    }

    /**
     * Show the form for creating a new Departamentos.
     *
     * @return Response
     */
    public function create()
    {
        $requerimientos = $this->requerimientosBase();

        return view('departamentos.create', compact('requerimientos'));
    }

    /**
     * Store a newly created Departamentos in storage.
     *
     * @param CreateDepartamentosRequest $request
     *
     * @return Response
     */

    public function store(CreateDepartamentosRequest $request): RedirectResponse
    {
        $input = $request->all();

        $normalize = static function (string $v): string {
            $v = trim($v);

            $v = str_replace("\u{00A0}", ' ', $v);

            $v = preg_replace('/\s+/u', ' ', $v) ?? $v;

            if (class_exists(Normalizer::class)) {
                $v = Normalizer::normalize($v, Normalizer::FORM_C) ?? $v;
            }

            return $v;
        };

        $selectedRaw = (array) $request->input('requerimientos', []);

        $selectedSet = [];
        foreach ($selectedRaw as $v) {
            if (!is_string($v)) continue;
            $k = $normalize($v);
            if ($k !== '') $selectedSet[$k] = true;
        }

        $departamentos = null;

        DB::transaction(function () use ($input, $selectedSet, $normalize, &$departamentos) {

            $departamentos = $this->departamentosRepository->create($input);
            $departamentoId = (int) $departamentos->DepartamentoID;

            $base = $this->requerimientosBase();

            $rows = [];
            foreach ($base as $categoria => $items) {
                foreach ($items as $item) {
                    $nombre = (string) $item['nombre'];
                    $nombreNorm = $normalize($nombre);

                    $rows[] = [
                        'DepartamentoID' => $departamentoId,
                        'categoria'      => $categoria,
                        'nombre'         => $nombre,
                        'seleccionado'   => isset($selectedSet[$nombreNorm]) ? 1 : 0,
                        'realizado'      => 0,
                        'opcional'       => !empty($item['opcional']) ? 1 : 0,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }
            }

            DB::table('departamento_requerimientos')->upsert(
                $rows,
                ['DepartamentoID', 'nombre'],
                ['categoria', 'seleccionado', 'realizado', 'opcional', 'updated_at']
            );
        });

        return redirect()
            ->route('departamentos.index')
            ->with('swal', [
                'icon'  => 'success',
                'title' => 'Departamento creado',
                'text'  => 'Se guardó el departamento y se generó su perfil de requerimientos.',
            ]);
    }

    private function requerimientosBase(): array
    {
        return [
            'Productos base' => [
                ['nombre' => 'Usuario Dominio'],
                ['nombre' => 'Net Driver'],
                ['nombre' => 'Office 365'],
                ['nombre' => 'VPN'],
                ['nombre' => 'Correo'],
                ['nombre' => 'Navegadores'],
                ['nombre' => 'Winrar'],
                ['nombre' => 'OneDrive'],
                ['nombre' => 'Adobe Reader'],
            ],

            'Programas Especiales' => [
                ['nombre' => 'ERP NEODATA'],
                ['nombre' => 'VS CONTROL'],
                ['nombre' => 'PU neodata'],
                ['nombre' => 'Adobe Cloud'],
                ['nombre' => 'Nomipaq'],
                ['nombre' => 'SUA'],
                ['nombre' => 'IDSE'],
                ['nombre' => 'Autocad LT'],
                ['nombre' => 'ERP VSCONTROL TOTAL'],
                ['nombre' => 'Acceso al OneDrive carpeta de control'],
                ['nombre' => 'Autocad Full', 'opcional' => true],
                ['nombre' => 'Revit', 'opcional' => true],
                ['nombre' => 'Project', 'opcional' => true],
                ['nombre' => 'Monday'],
                ['nombre' => 'COMPAQ I FACTURACIÓN'],
                ['nombre' => 'MIADMIN XML PRO'],
                ['nombre' => 'Acceso al NASS de Presupuestos'],
                ['nombre' => 'Cuenta en modo visor de Monday'],
            ],

            'Carpetas' => [
                ['nombre' => 'Comprobantes de pago ext(1.9)'],
                ['nombre' => 'Capeta de Compras 0301_Compras'],
                ['nombre' => '0301_Compras_of'],
                ['nombre' => 'Gestión de proveedores (SharePoint Compras > Proveedores)'],
                ['nombre' => 'Carpeta de cierre de obra (Sharepoint CIERRE DE OBRA)'],
                ['nombre' => 'Acceso a todas las carpetas del onedrive de Jurídico'],
            ],

            'Escaner' => [
                ['nombre' => 'Escáner'],
            ],

            'Impresora' => [
                ['nombre' => 'Impresora ext (2.43)'],
                ['nombre' => 'Impresora ext (2.42)'],
                ['nombre' => 'Impresora ext (2.41)'],
                ['nombre' => 'Brother DCP-L2540DW'],
                ['nombre' => 'Epson L355'],
                ['nombre' => 'Brother MFC-L8900cdw'],
                ['nombre' => 'HP LASERJET M141W'],
                ['nombre' => 'Impresora ext (1.253)'],
            ],
        ];
    }

    /**
     * Display the specified Departamentos.
     *
     * @param int $id
     *
     * @return Response
     */

    public function show($id): View|RedirectResponse
    {
        $departamentos = $this->departamentosRepository->find($id);

        if (empty($departamentos)) {
            return redirect()
                ->route('departamentos.index')
                ->with('swal', [
                    'icon'  => 'error',
                    'title' => 'No encontrado',
                    'text'  => 'El departamento no existe.',
                ]);
        }

        $requerimientosSeleccionados = DepartamentoRequerimientos::query()
            ->byDepartamentos((int) $id)
            ->seleccionados()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get(['categoria', 'nombre', 'opcional', 'realizado']);

        $seleccionadosPorCategoria = $requerimientosSeleccionados->groupBy('categoria');

        return view('departamentos.show', [
            'departamentos' => $departamentos,
            'requerimientosSeleccionados' => $requerimientosSeleccionados,
            'seleccionadosPorCategoria' => $seleccionadosPorCategoria,
        ]);
    }

    /**
     * Show the form for editing the specified Departamentos.
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function edit($id): View|RedirectResponse
    {
        $departamentos = $this->departamentosRepository->find($id);

        if (empty($departamentos)) {
            return redirect()
                ->route('departamentos.index')
                ->with('swal', [
                    'icon'  => 'error',
                    'title' => 'No encontrado',
                    'text'  => 'El departamento no existe.',
                ]);
        }

        $departamentoId = (int) $departamentos->DepartamentoID;

        $base = $this->requerimientosBase();

        $seleccionados = DepartamentoRequerimientos::query()
            ->byDepartamentos($departamentoId)
            ->seleccionados()
            ->pluck('nombre')
            ->map(fn($v) => is_string($v) ? trim($v) : $v)
            ->toArray();

        $seleccionadosSet = array_fill_keys($seleccionados, true);

        foreach ($base as $categoria => &$items) {
            foreach ($items as &$item) {
                $nombre = $item['nombre'];
                $item['seleccionado'] = isset($seleccionadosSet[$nombre]);
            }
        }
        unset($items, $item);

        return view('departamentos.edit', [
            'departamentos'  => $departamentos,
            'requerimientos' => $base,
        ]);
    }

    /**
     * Update the specified Departamentos in storage.
     *
     * @param int $id
     * @param UpdateDepartamentosRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateDepartamentosRequest $request): RedirectResponse
    {
        $departamentos = $this->departamentosRepository->find($id);

        if (empty($departamentos)) {
            return redirect()
                ->route('departamentos.index')
                ->with('swal', ['icon' => 'error', 'title' => 'No encontrado', 'text' => 'El departamento no existe.']);
        }

        $input = $request->all();

        $rawSelected = (array) $request->input('requerimientos', []);

        $normalize = function ($v) {
            return trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $v)));
        };

        $selectedSet = [];
        foreach ($rawSelected as $v) {
            if (is_string($v) && $v !== '') {
                $selectedSet[$normalize($v)] = true;
            }
        }

        $base = $this->requerimientosBase();
        $rows = [];
        $now = now();

        foreach ($base as $categoria => $items) {
            foreach ($items as $item) {
                $nombre = (string) $item['nombre'];
                $nombreNorm = $normalize($nombre);

                $rows[] = [
                    'DepartamentoID' => (int) $id,
                    'categoria'      => $categoria,
                    'nombre'         => $nombre,
                    'seleccionado'   => isset($selectedSet[$nombreNorm]) ? 1 : 0,
                    'opcional'       => !empty($item['opcional']) ? 1 : 0,
                ];
            }
        }

        DB::transaction(function () use ($id, $input, $rows) {
            $this->departamentosRepository->update($input, $id);

            DB::table('departamento_requerimientos')->upsert(
                $rows,
                ['DepartamentoID', 'nombre'],
                ['seleccionado', 'categoria', 'opcional', 'updated_at']
            );
        });

        return redirect()
            ->route('departamentos.index')
            ->with('swal', [
                'icon'  => 'success',
                'title' => 'Departamento actualizado',
                'text'  => 'Se actualizó el departamento y sus requerimientos correctamente.',
            ]);
    }

    /**
     * Remove the specified Departamentos from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $departamentos = $this->departamentosRepository->find($id);

        if (empty($departamentos)) {
            Flash::error('Departamentos not found');

            return redirect(route('departamentos.index'));
        }

        $this->departamentosRepository->delete($id);

        Flash::success('Departamentos deleted successfully.');

        return redirect(route('departamentos.index'));
    }
}
