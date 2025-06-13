<?php

namespace App\Http\Controllers;

use App\DataTables\ReportesDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateReportesRequest;
use App\Http\Requests\UpdateReportesRequest;
use App\Repositories\ReportesRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\Reportes;
use Response;
use Stringable;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


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
            Flash::error('Reportes not found');

            return redirect(route('reportes.index'));
        }

        try {
            $sql = trim($reportes->query_details);

            if (Str::startsWith($sql, '"') && Str::endsWith($sql, '"')) {
                $sql = substr($sql, 1, -1);
            }

            $resultado = DB::select($sql);
        } catch (\Exception $e) {
            return redirect()
                ->route('reportes.index')
                ->with('error', 'Error al ejecutar el query: ' . $e->getMessage());
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
            Flash::error('Reportes not found');

            return redirect(route('reportes.index'));
        }

        return view('reportes.edit')->with('reportes', $reportes);
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
            Flash::error('Reportes not found');

            return redirect(route('reportes.index'));
        }

        $reportes = $this->reportesRepository->update($request->all(), $id);

        Flash::success('Reportes updated successfully.');

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
}
