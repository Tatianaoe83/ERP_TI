@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Puestos"
    icon="fa-briefcase"
    :create-url="route('puestos.create')"
    create-permission="crear-puestos"
    create-label="+ Nuevo puesto"
>
    <x-slot name="filters">
        <form method="GET">
            <div class="form-group">
                <label for="filtro_area">Área</label>
                <select id="filtro_area" name="area_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas las áreas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->DepartamentoID }}" {{ request('area_id') == $area->DepartamentoID ? 'selected' : '' }}>
                            {{ $area->gerencia->NombreGerencia ?? '' }} / {{ $area->NombreDepartamento }}
                        </option>
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
                    <th>Área</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($puestos as $puesto)
                    @php
                        $area = $puesto->departamentos;
                        $gerencia = $area->gerencia ?? null;
                        $direccion = $gerencia->direccion ?? null;
                    @endphp
                    <tr>
                        <td>{{ $puesto->NombrePuesto }}</td>
                        <td>{{ $direccion->NombreDireccion ?? '—' }}</td>
                        <td>{{ $gerencia->NombreGerencia ?? '—' }}</td>
                        <td>{{ $area->NombreDepartamento ?? '—' }}</td>
                        <td><span class="index-badge index-badge--success">Activo</span></td>
                        <td>
                            <x-index-actions
                                :edit-url="route('puestos.edit', $puesto->PuestoID)"
                                edit-permission="editar-puestos"
                                :destroy-route="['puestos.destroy', $puesto->PuestoID]"
                                destroy-permission="borrar-puestos"
                                confirm-title="¿Está seguro de que desea borrar este puesto?"
                                success-title="Puesto borrado"
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
