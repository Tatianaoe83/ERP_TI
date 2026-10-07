@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Direcciones"
    icon="fa-sitemap"
    :create-url="route('direcciones.create')"
    create-permission="crear-gerencias"
    create-label="+ Nueva dirección"
>
    <x-slot name="filters">
        <form method="GET">
            <div class="form-group">
                <label for="filtro_unidad">Unidad de negocio</label>
                <select id="filtro_unidad" name="unidad_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas las unidades de negocio</option>
                    @foreach ($unidades as $unidad)
                        <option value="{{ $unidad->UnidadNegocioID }}" {{ request('unidad_id') == $unidad->UnidadNegocioID ? 'selected' : '' }}>{{ $unidad->NombreEmpresa }}</option>
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
                    <th>Unidad de negocio</th>
                    <th>Gerencias</th>
                    <th>Áreas</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($direcciones as $direccion)
                    <tr>
                        <td>{{ $direccion->NombreDireccion }}</td>
                        <td>{{ $direccion->unidadesdenegocio->NombreEmpresa ?? '—' }}</td>
                        <td>{{ $direccion->gerencias_count }}</td>
                        <td>{{ $direccion->departamentos_count }}</td>
                        <td>
                            @if($direccion->estado)
                                <span class="index-badge index-badge--success">Activo</span>
                            @else
                                <span class="index-badge index-badge--muted">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <x-index-actions
                                :edit-url="route('direcciones.edit', $direccion->DireccionID)"
                                edit-permission="editar-gerencias"
                                :destroy-route="['direcciones.destroy', $direccion->DireccionID]"
                                destroy-permission="borrar-gerencias"
                                confirm-title="¿Está seguro de que desea borrar esta dirección?"
                                success-title="Dirección borrada"
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
