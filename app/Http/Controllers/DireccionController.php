<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateDireccionRequest;
use App\Models\UnidadesDeNegocio;
use App\Http\Requests\UpdateDireccionRequest;
use App\Models\Direccion;
use Flash;

class DireccionController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('permission:ver-gerencias|crear-gerencias|editar-gerencias|borrar-gerencias')->only('index', 'show');
        $this->middleware('permission:crear-gerencias')->only('create', 'store');
        $this->middleware('permission:editar-gerencias')->only('edit', 'update');
        $this->middleware('permission:borrar-gerencias')->only('destroy');
    }

    public function index()
    {
        $direcciones = Direccion::with('unidadesdenegocio')
            ->withCount(['gerencias', 'departamentos'])
            ->when(request('unidad_id'), function ($query, $unidadId) {
                $query->where('UnidadNegocioID', $unidadId);
            })
            ->orderBy('NombreDireccion')
            ->get();

        $unidades = UnidadesDeNegocio::orderBy('NombreEmpresa')->get(['UnidadNegocioID', 'NombreEmpresa']);

        return view('direcciones.index', compact('direcciones', 'unidades'));
    }

    public function create()
    {
        return view('direcciones.create');
    }

    public function store(CreateDireccionRequest $request)
    {
        Direccion::create($request->all());

        Flash::success('Dirección guardada.');

        return redirect(route('direcciones.index'));
    }

    public function show($id)
    {
        $direccion = Direccion::with('unidadesdenegocio')->find($id);

        if (empty($direccion)) {
            Flash::error('Dirección no encontrada');

            return redirect(route('direcciones.index'));
        }

        return view('direcciones.show', compact('direccion'));
    }

    public function edit($id)
    {
        $direccion = Direccion::find($id);

        if (empty($direccion)) {
            Flash::error('Dirección no encontrada');

            return redirect(route('direcciones.index'));
        }

        return view('direcciones.edit', compact('direccion'));
    }

    public function update($id, UpdateDireccionRequest $request)
    {
        $direccion = Direccion::find($id);

        if (empty($direccion)) {
            Flash::error('Dirección no encontrada');

            return redirect(route('direcciones.index'));
        }

        $direccion->update($request->all());

        // La unidad de la gerencia sale de su dirección, para que la cadena no se parta.
        $direccion->gerencias()->update(['UnidadNegocioID' => $direccion->UnidadNegocioID]);

        Flash::success('Dirección actualizada.');

        return redirect(route('direcciones.index'));
    }

    public function destroy($id)
    {
        $direccion = Direccion::withCount('gerencias')->find($id);

        if (empty($direccion)) {
            Flash::error('Dirección no encontrada');

            return redirect(route('direcciones.index'));
        }

        if ($direccion->gerencias_count > 0) {
            Flash::error('No se puede eliminar: tiene gerencias. En TI la cadena no puede quedar vacía.');

            return redirect(route('direcciones.index'));
        }

        $direccion->delete();

        Flash::success('Dirección eliminada.');

        return redirect(route('direcciones.index'));
    }
}
