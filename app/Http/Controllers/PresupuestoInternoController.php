<?php

namespace App\Http\Controllers;

use App\Models\Empleados;
use App\Models\PresupuestoInterno;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PresupuestoInternoController extends AppBaseController
{
    public function __construct()
    {
        $this->middleware('permission:ver-empleados')->only('index');
        $this->middleware('permission:editar-empleados')->only('store', 'update', 'destroy');
    }

    public function index()
    {
        $presupuestos = PresupuestoInterno::query()
            ->withCount('empleados')
            ->orderBy('Nombre')
            ->get();

        return view('presupuestos_internos.index', compact('presupuestos'));
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        PresupuestoInterno::create($datos);
        Flash::success('Presupuesto interno agregado.');

        return redirect()->route('presupuestos-internos.index');
    }

    public function update(Request $request, $presupuestos_interno)
    {
        $presupuesto = PresupuestoInterno::query()->findOrFail($presupuestos_interno);
        $presupuesto->fill($this->validar($request, (int) $presupuestos_interno))->save();
        Flash::success('Presupuesto interno actualizado.');

        return redirect()->route('presupuestos-internos.index');
    }

    public function destroy($presupuestos_interno)
    {
        $presupuesto = PresupuestoInterno::query()->findOrFail($presupuestos_interno);
        $enUso = Empleados::query()->where('PresupuestoInternoID', $presupuesto->PresupuestoInternoID)->exists();
        if ($enUso) {
            Flash::error('Hay empleados con este presupuesto. Cámbialos antes de borrarlo.');

            return redirect()->route('presupuestos-internos.index');
        }

        $presupuesto->delete();
        Flash::success('Presupuesto interno eliminado.');

        return redirect()->route('presupuestos-internos.index');
    }

    private function validar(Request $request, ?int $id = null): array
    {
        $request->merge([
            'Nombre' => mb_strtoupper(trim((string) $request->input('Nombre')), 'UTF-8'),
        ]);

        $datos = $request->validate([
            'Nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('presupuestos_internos', 'Nombre')->ignore($id, 'PresupuestoInternoID')->whereNull('deleted_at'),
            ],
        ]);

        return ['Nombre' => $datos['Nombre']];
    }
}
