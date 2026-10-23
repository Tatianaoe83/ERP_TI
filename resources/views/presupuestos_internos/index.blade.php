@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Presupuesto interno"
    icon="fa-file-invoice"
    subtitle="Aquí se define a dónde va el presupuesto de cada empleado."
    :show-count="false"
>
    @if(auth()->user()->can('editar-empleados'))
        <form method="POST" action="{{ route('presupuestos-internos.store') }}" class="index-page__card crud-page__card" style="margin-bottom:1rem;">
            @csrf
            <div class="row crud-form">
                <div class="col-sm-8">
                    <label for="Nombre">Nombre</label>
                    <input type="text" name="Nombre" id="Nombre" class="form-control" maxlength="150" required value="{{ old('Nombre') }}" placeholder="Ej. G601 TI DIRECCION ADM Y FINANZAS" @error('Nombre') style="border-color:#f87171;" @enderror>
                    @error('Nombre')
                        <p role="alert" style="margin:.45rem 0 0;color:#fca5a5;font-size:.85rem;font-weight:600;">{{ $message }}</p>
                    @enderror
                </div>
                <div class="col-sm-4 d-flex align-items-end">
                    <button type="submit" class="index-page__btn-primary">Agregar</button>
                </div>
            </div>
        </form>
    @endif

    <div class="index-page__table-wrap table-responsive">
        <table class="table index-table w-full">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Empleados</th>
                    @if(auth()->user()->can('editar-empleados'))
                        <th>Acción</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($presupuestos as $presupuesto)
                    <tr>
                        <td>
                            @if(auth()->user()->can('editar-empleados'))
                                <form method="POST" action="{{ route('presupuestos-internos.update', $presupuesto->PresupuestoInternoID) }}" style="display:flex;gap:.5rem;align-items:center;">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="Nombre" class="form-control" maxlength="150" required value="{{ $presupuesto->Nombre }}">
                                    <button type="submit" class="index-page__btn-secondary">Guardar</button>
                                </form>
                            @else
                                {{ $presupuesto->Nombre }}
                            @endif
                        </td>
                        <td><span class="erp-chip erp-chip--info">{{ $presupuesto->empleados_count }}</span></td>
                        @if(auth()->user()->can('editar-empleados'))
                            <td>
                                <form method="POST" action="{{ route('presupuestos-internos.destroy', $presupuesto->PresupuestoInternoID) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="index-page__btn-secondary index-action-confirm" data-confirm-title="¿Eliminar este presupuesto interno?">Eliminar</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Todavía no hay presupuestos internos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-index-page>
@push('third_party_scripts')
@include('layouts.partials.index-page-js')
@endpush
@endsection
