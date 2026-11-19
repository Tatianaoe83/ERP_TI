<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateDivisionRequest;
use App\Http\Requests\UpdateDivisionRequest;
use App\Models\Division;
use Flash;

class DivisionController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('permission:ver-unidadesdenegocio|crear-unidadesdenegocio|editar-unidadesdenegocio|borrar-unidadesdenegocio')->only('index', 'show');
        $this->middleware('permission:crear-unidadesdenegocio')->only('create', 'store');
        $this->middleware('permission:editar-unidadesdenegocio')->only('edit', 'update');
        $this->middleware('permission:borrar-unidadesdenegocio')->only('destroy');
    }

    public function index()
    {
        $divisiones = Division::withCount('unidadesdenegocio')
            ->orderBy('NombreDivision')
            ->get();

        return view('divisiones.index', compact('divisiones'));
    }

    public function create()
    {
        return view('divisiones.create');
    }

    public function store(CreateDivisionRequest $request)
    {
        Division::create($request->all());

        Flash::success('División guardada.');

        return redirect(route('divisiones.index'));
    }

    public function show($id)
    {
        $division = Division::find($id);

        if (empty($division)) {
            Flash::error('División no encontrada');

            return redirect(route('divisiones.index'));
        }

        return view('divisiones.show', compact('division'));
    }

    public function edit($id)
    {
        $division = Division::find($id);

        if (empty($division)) {
            Flash::error('División no encontrada');

            return redirect(route('divisiones.index'));
        }

        return view('divisiones.edit', compact('division'));
    }

    public function update($id, UpdateDivisionRequest $request)
    {
        $division = Division::find($id);

        if (empty($division)) {
            Flash::error('División no encontrada');

            return redirect(route('divisiones.index'));
        }

        $division->update($request->all());

        Flash::success('División actualizada.');

        return redirect(route('divisiones.index'));
    }

    public function destroy($id)
    {
        $division = Division::withCount('unidadesdenegocio')->find($id);

        if (empty($division)) {
            Flash::error('División no encontrada');

            return redirect(route('divisiones.index'));
        }

        if ($division->unidadesdenegocio_count > 0) {
            Flash::error('No se puede eliminar: tiene unidades de negocio. En TI la cadena no puede quedar vacía.');

            return redirect(route('divisiones.index'));
        }

        $division->delete();

        Flash::success('División eliminada.');

        return redirect(route('divisiones.index'));
    }
}
