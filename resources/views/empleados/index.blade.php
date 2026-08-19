@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Empleados"
    icon="fa-user"
    :create-url="route('empleados.create')"
    create-permission="crear-empleados"
>
    <x-slot name="filters">
        <div class="form-group">
            <label for="filtro_nombre">Nombre</label>
            <input type="text" id="filtro_nombre" class="form-control" placeholder="Buscar por nombre...">
        </div>
        <div class="form-group">
            <label for="filtro_tipo_persona">Tipo de persona</label>
            <select id="filtro_tipo_persona" class="jz1 form-control">
                <option value="">Todos los tipos</option>
                <option value="FISICA">FISICA</option>
                <option value="REFERENCIADO">REFERENCIADO</option>
                <option value="EXTRAORDINARIO">EXTRAORDINARIO</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filtro_estado">Estado</label>
            <select id="filtro_estado" class="jz1 form-control">
                <option value="">Todos</option>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filtro_gerencia">Gerencia</label>
            <select id="filtro_gerencia" class="jz1 form-control">
                <option value="">Todas las gerencias</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filtro_departamento">Departamento</label>
            <select id="filtro_departamento" class="jz1 form-control">
                <option value="">Todos los departamentos</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filtro_puesto">Puesto</label>
            <select id="filtro_puesto" class="jz1 form-control">
                <option value="">Todos los puestos</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filtro_obra">Obra</label>
            <select id="filtro_obra" class="jz1 form-control">
                <option value="">Todas las obras</option>
            </select>
        </div>
        <div class="form-group d-flex align-items-end">
            <button id="limpiarFiltros" type="button" class="index-page__btn-primary" style="background:#6b7280;">
                <i class="fa fa-times"></i> Limpiar filtros
            </button>
        </div>
    </x-slot>

    @include('empleados.table')
</x-index-page>
@endsection
