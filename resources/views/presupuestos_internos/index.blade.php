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
        <x-slot name="filters">
            <form method="POST" action="{{ route('presupuestos-internos.store') }}">
                @csrf
                <div class="form-group">
                    <label for="Nombre">Nombre</label>
                    <input type="text" name="Nombre" id="Nombre" class="form-control" maxlength="150" required value="{{ old('Nombre') }}" placeholder="Ej. G601 TI DIRECCION ADM Y FINANZAS" @error('Nombre') style="border-color:#f87171;" @enderror>
                    @error('Nombre')
                        <p role="alert" style="margin:.45rem 0 0;color:#fca5a5;font-size:.85rem;font-weight:600;">{{ $message }}</p>
                    @enderror
                </div>
                <div class="form-group d-flex align-items-end">
                    <button type="submit" class="index-page__btn-primary">Agregar</button>
                </div>
            </form>
        </x-slot>
    @endif

    <div class="index-page__table-wrap table-responsive">
        <table class="table index-table w-full js-index-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Empleados</th>
                    @if(auth()->user()->can('editar-empleados'))
                        <th>Acciones</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($presupuestos as $presupuesto)
                    <tr>
                        <td data-order="{{ $presupuesto->Nombre }}">
                            <span class="pi-nombre">{{ $presupuesto->Nombre }}</span>
                            @if(auth()->user()->can('editar-empleados'))
                                <form id="pi-{{ $presupuesto->PresupuestoInternoID }}" class="pi-form" method="POST" action="{{ route('presupuestos-internos.update', $presupuesto->PresupuestoInternoID) }}" hidden>
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="Nombre" class="form-control" maxlength="150" required value="{{ $presupuesto->Nombre }}">
                                </form>
                            @endif
                        </td>
                        <td data-order="{{ $presupuesto->empleados_count }}">{{ $presupuesto->empleados_count }}</td>
                        @if(auth()->user()->can('editar-empleados'))
                            <td>
                                <div class="index-actions">
                                    <button type="button" class="index-action index-action--edit pi-editar" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="submit" form="pi-{{ $presupuesto->PresupuestoInternoID }}" class="index-action index-action--success pi-guardar" title="Guardar" hidden>
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="index-action pi-cancelar" title="Cancelar" hidden>
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <form method="POST" action="{{ route('presupuestos-internos.destroy', $presupuesto->PresupuestoInternoID) }}" class="index-action-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="index-action index-action--delete index-action-confirm" title="Eliminar" data-confirm-title="¿Eliminar este presupuesto interno?" data-success-title="Presupuesto interno eliminado">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-index-page>
@endsection

@push('styles')
<style>
    .pi-form { display: flex; align-items: center; margin: 0; }
    .pi-form[hidden] { display: none !important; }
    .pi-form .form-control { min-height: 2.25rem; }
</style>
@endpush

@include('layouts.partials.index-client-table')

@push('third_party_scripts')
<script>
    document.addEventListener('click', function (event) {
        var editar = event.target.closest('.pi-editar');
        if (editar) {
            var fila = editar.closest('tr');
            if (!fila) return;
            fila.querySelector('.pi-nombre').hidden = true;
            fila.querySelector('.pi-form').hidden = false;
            editar.hidden = true;
            fila.querySelector('.pi-guardar').hidden = false;
            fila.querySelector('.pi-cancelar').hidden = false;
            var campo = fila.querySelector('.pi-form input');
            if (campo) campo.focus();
            return;
        }

        var cancelar = event.target.closest('.pi-cancelar');
        if (!cancelar) return;
        var filaCancelar = cancelar.closest('tr');
        if (!filaCancelar) return;
        var form = filaCancelar.querySelector('.pi-form');
        var nombre = filaCancelar.querySelector('.pi-nombre');
        var input = form.querySelector('input');
        if (input && nombre) input.value = nombre.textContent.trim();
        form.hidden = true;
        nombre.hidden = false;
        filaCancelar.querySelector('.pi-editar').hidden = false;
        filaCancelar.querySelector('.pi-guardar').hidden = true;
        cancelar.hidden = true;
    });
</script>
@endpush
