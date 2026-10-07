@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Unidades de negocio"
    icon="fa-city"
    :create-url="route('unidadesDeNegocios.create')"
    create-permission="crear-unidadesdenegocio"
    create-label="+ Nueva unidad"
>
    <x-slot name="filters">
        <form method="GET">
            <div class="form-group">
                <label for="filtro_division">División</label>
                <select id="filtro_division" name="division_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas las divisiones</option>
                    @foreach ($divisiones as $division)
                        <option value="{{ $division->DivisionID }}" {{ request('division_id') == $division->DivisionID ? 'selected' : '' }}>{{ $division->NombreDivision }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </x-slot>

    <div class="index-page__table-wrap table-responsive">
        <table class="table index-table w-full js-index-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>División</th>
                    <th>Direcciones</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($unidades as $unidad)
                    <tr>
                        <td>{{ $unidad->NombreEmpresa }}</td>
                        <td>{{ $unidad->division->NombreDivision ?? '—' }}</td>
                        <td>{{ $unidad->direcciones_count }}</td>
                        <td>
                            @if($unidad->estado)
                                <span class="index-badge index-badge--success">Activo</span>
                            @else
                                <span class="index-badge index-badge--muted">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <x-index-actions
                                :edit-url="route('unidadesDeNegocios.edit', $unidad->UnidadNegocioID)"
                                edit-permission="editar-unidadesdenegocio"
                                :destroy-route="['unidadesDeNegocios.destroy', $unidad->UnidadNegocioID]"
                                destroy-permission="borrar-unidadesdenegocio"
                                confirm-title="¿Está seguro de que desea borrar esta unidad de negocio?"
                                success-title="Unidad borrada"
                            />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-index-page>
@endsection

@include('layouts.partials.index-client-table')
