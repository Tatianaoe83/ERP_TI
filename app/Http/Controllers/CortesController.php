<?php

namespace App\Http\Controllers;

use App\DataTables\CortesDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateCortesRequest;
use App\Http\Requests\UpdateCortesRequest;
use App\Repositories\CortesRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\Cortes;
use App\Models\Gerencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Js;
use Response;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Editor\Fields\Select;

class CortesController extends AppBaseController
{
    /** @var CortesRepository $cortesRepository*/
    private $cortesRepository;

    public function __construct(CortesRepository $cortesRepo)
    {
        $this->cortesRepository = $cortesRepo;
    }

    /**
     * Display a listing of the Cortes.
     *
     * @param CortesDataTable $cortesDataTable
     *
     * @return Response
     */
    /* public function index(CortesDataTable $cortesDataTable)
    {
        return $cortesDataTable->render('cortes.index');
    } */

    public function index(Request $request)
    {
        $gerencia = Gerencia::all();

        $meses = [
            'Enero',
            'Febrero',
            'Marzo',
            'Abril',
            'Mayo',
            'Junio',
            'Julio',
            'Agosto',
            'Septiembre',
            'Octubre',
            'Noviembre',
            'Diciembre'
        ];

        if ($gerenciaID = $request->input('gerenciaID')) {

            $exist = Cortes::where('GerenciaID', $gerenciaID)->exists();
            if ($exist) {
                return redirect()->back()->with('error', 'Corte de esta gerencia ya fue realizado.');
            }

            try {
                $resultados = DB::select('CALL ObtenerInsumosAnualesPorGerencia6(?)', [$gerenciaID]);

                foreach ($resultados as $fila) {
                    Cortes::create([
                        'NombreInsumo' => $fila->NombreInsumo ?? '',
                        'Mes' => $fila->Mes ?? '',
                        'Costo' => $fila->Costo ?? 0,
                        'GerenciaID' => $fila->GerenciaID
                    ]);
                }

                return redirect()->back()->with('success', 'Corte realizado correctamente.');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error al realizar el corte');
            }
        }

        return view('cortes.index', compact('meses', 'gerencia'));
    }

    public function indexVista(Request $request)
    {
        $gerenciaID = $request->input('gerenci_id');
        $mes = $request->input('mes');

        if ($request->ajax()) {
            if ($gerenciaID && $mes) {
                $query = DB::table('cortes')
                    ->where('GerenciaID', $gerenciaID)
                    ->where('Mes', $mes);

                return DataTables::of($query)
                    ->addColumn('action', function ($row) {
                        return view('cortes.datatables_actions', ['id' => $row->CortesID])->render();
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            } else {
                return DataTables::of(collect([]))->make(true);
            }
        }

        return view('cortes.index');
    }


    public function readXml(Request $request)
    {
        $request->validate([
            'imagen' => 'required|mimes:xml|max:2048'
        ]);

        $file = $request->file('imagen');
        $content = file_get_contents($file->getRealPath());

        libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($content);
            if ($xml === false) {
                throw new RuntimeException('XML inválido');
            }
            $namespaces = $xml->getDocNamespaces(true);
            $cfdiUri = $namespaces['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4';
            $xml->registerXPathNamespace('cfdi', $cfdiUri);

            $emisorNode = $xml->xpath('//cfdi:Comprobante/cfdi:Emisor');
            $nombreEmisor = $emisorNode ? (string) $emisorNode[0]['Nombre'] : null;

            $conceptos = $xml->xpath('//cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto') ?: [];

            $insumos = Insumos::select(['ID', 'NombreInsumo'])->get();
            $catalogo = $insumos->map(fn($insumo) => [
                'id'     => $insumo->ID,
                'nombre' => $insumo->NombreInsumo,
                'norm'   => $this->normalizeText($insumo->NombreInsumo),
            ])->all();

            $UMBRAL = 60;
            $datos = [];

            foreach ($conceptos as $concepto) {
                $descripcion = (string) ($concepto['Descripcion'] ?? '');
                $importe     = (float)  ($concepto['Importe'] ?? 0);

                [$best, $score] = $this->matchInsumo($descripcion, $catalogo);

                if (($best === null || $score < $UMBRAL) && $nombreEmisor) {
                    $normEmisor = $this->normalizeText($nombreEmisor);
                    if (str_contains($normEmisor, 'starlink')) {
                        $star = $this->matchPorKeyword('starlink', $catalogo);
                        if ($star) {
                            $best  = $star;
                            $score = 95;
                        }
                    }
                }

                $datos[] = [
                    'insumo'       => $best['nombre'] ?? null,
                    'insumo_id'    => $best['id'] ?? null,
                    'descripcion'  => $descripcion,
                    'importe'      => $importe,
                    'confianza'    => $score ?? 0,
                    'emisor'       => $nombreEmisor,
                ];
            }

            return response()->json([
                'success' => 'XML leído con éxito',
                'emisor'  => $nombreEmisor,
                'datos'   => $datos
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error al leer XML: ' . $e->getMessage()], 422);
        }
    }

    private function normalizeText(string $txt): string
    {
        $txt = mb_strtolower($txt, 'UTF-8');
        $txt = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $txt);
        $txt = preg_replace('/[^a-z0-9\s]/i', ' ', $txt);
        $txt = preg_replace('/\s+/', ' ', $txt);
        return trim($txt ?? '');
    }

    private function matchInsumo(string $descripcion, array $catalogo): array
    {
        $normDesc = $this->normalizeText($descripcion);
        if ($normDesc === '') return [null, 0];

        $best = null;
        $bestScore = 0;

        foreach ($catalogo as $row) {
            if ($row['norm'] !== '' && mb_stripos($normDesc, $row['norm']) !== false) {
                return [$row, 95];
            }
            if ($normDesc !== '' && mb_stripos($row['norm'], $normDesc) !== false) {
                $best = $row;
                $bestScore = max($bestScore, 85);
                continue;
            }
            similar_text($normDesc, $row['norm'], $pct);
            if ($pct > $bestScore) {
                $bestScore = $pct;
                $best = $row;
            }
        }

        return ($best && $bestScore >= 60) ? [$best, round($bestScore, 2)] : [null, round($bestScore, 2)];
    }

    private function matchPorKeyword(string $keyword, array $catalogo): ?array
    {
        $k = $this->normalizeText($keyword);
        foreach ($catalogo as $row) {
            if ($row['norm'] !== '' && str_contains($row['norm'], $k)) {
                return $row;
            }
        }
        return null;
    }


    /**
     * Show the form for creating a new Cortes.
     *
     * @return Response
     */
    public function create()
    {

        return view('cortes.create');
    }

    /**
     * Store a newly created Cortes in storage.
     *
     * @param CreateCortesRequest $request
     *
     * @return Response
     */
    public function store(CreateCortesRequest $request)
    {
        $input = $request->all();

        $cortes = $this->cortesRepository->create($input);

        Flash::success('Cortes saved successfully.');

        return redirect(route('cortes.index'));
    }

    /**
     * Display the specified Cortes.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $cortes = $this->cortesRepository->find($id);

        if (empty($cortes)) {
            Flash::error('Cortes not found');

            return redirect(route('cortes.index'));
        }

        return view('cortes.show')->with('cortes', $cortes);
    }

    /**
     * Show the form for editing the specified Cortes.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $cortes = $this->cortesRepository->find($id);

        if (empty($cortes)) {
            Flash::error('Cortes not found');

            return redirect(route('cortes.index'));
        }

        return view('cortes.edit')->with('cortes', $cortes);
    }

    /**
     * Update the specified Cortes in storage.
     *
     * @param int $id
     * @param UpdateCortesRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateCortesRequest $request)
    {
        $cortes = $this->cortesRepository->find($id);

        if (empty($cortes)) {
            Flash::error('Cortes not found');

            return redirect(route('cortes.index'));
        }

        $cortes = $this->cortesRepository->update($request->all(), $id);

        Flash::success('Cortes updated successfully.');

        return redirect(route('cortes.index'));
    }

    /**
     * Remove the specified Cortes from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $cortes = $this->cortesRepository->find($id);

        if (empty($cortes)) {
            Flash::error('Cortes not found');

            return redirect(route('cortes.index'));
        }

        $this->cortesRepository->delete($id);

        Flash::success('Cortes deleted successfully.');

        return redirect(route('cortes.index'));
    }
}
