@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Áreas"
    icon="fa-tags"
    :create-url="route('departamentos.create')"
    create-permission="crear-departamentos"
    create-label="+ Nueva área"
>
    <x-slot name="filters">
        <form method="GET">
            <div class="form-group">
                <label for="filtro_direccion">Dirección</label>
                <select id="filtro_direccion" name="direccion_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas las direcciones</option>
                    @foreach ($direcciones as $direccion)
                        <option value="{{ $direccion->DireccionID }}" {{ request('direccion_id') == $direccion->DireccionID ? 'selected' : '' }}>{{ $direccion->NombreDireccion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="filtro_gerencia">Gerencia</label>
                <select id="filtro_gerencia" name="gerencia_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas las gerencias</option>
                    @foreach ($gerencias as $gerencia)
                        <option value="{{ $gerencia->GerenciaID }}" {{ request('gerencia_id') == $gerencia->GerenciaID ? 'selected' : '' }}>{{ $gerencia->NombreGerencia }}</option>
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
                    <th>Dirección</th>
                    <th>Gerencia</th>
                    <th>Puestos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($areas as $area)
                    <tr>
                        <td>{{ $area->NombreDepartamento }}</td>
                        <td>{{ $area->gerencia->direccion->NombreDireccion ?? '—' }}</td>
                        <td>{{ $area->gerencia->NombreGerencia ?? '—' }}</td>
                        <td>{{ $area->puestos_count }}</td>
                        <td><span class="index-badge index-badge--success">Activo</span></td>
                        <td>
                            <x-index-actions
                                :edit-url="route('departamentos.edit', $area->DepartamentoID)"
                                edit-permission="editar-departamentos"
                                :destroy-route="['departamentos.destroy', $area->DepartamentoID]"
                                destroy-permission="borrar-departamentos"
                                confirm-title="¿Está seguro de que desea borrar esta área?"
                                success-title="Área borrada"
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
