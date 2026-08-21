@extends('layouts.app')

@section('content')
<x-index-page
    title="Equipos asignados"
    icon="fa-laptop"
    subtitle="0 registros"
>
    <x-slot name="headerActions">
        @can('exportar-reportes-especificos')
        <a href="{{ route('reportes-especificos.export-equipos-asignados-excel', $filtros) }}" class="index-page__btn-primary">
            <i class="fas fa-file-excel"></i> Descargar Excel
        </a>
        @endcan
        <a href="{{ route('reportes-especificos.index') }}" class="index-page__btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </x-slot>

    <x-slot name="filters">
        <form method="GET" action="{{ route('reportes-especificos.equipos-asignados') }}">
            <div class="form-group">
                <label for="empleado_id">Empleado</label>
                <select id="empleado_id" name="empleado_id" class="jz form-control">
                    <option value="">Todos los empleados</option>
                    @foreach(\App\Models\Empleados::whereNull('deleted_at')->get() as $empleado)
                        <option value="{{ $empleado->EmpleadoID }}" {{ (string) $filtros['empleado_id'] === (string) $empleado->EmpleadoID ? 'selected' : '' }}>
                            {{ $empleado->NombreEmpleado }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="marca">Marca</label>
                <select id="marca" name="marca" class="jz form-control">
                    <option value="">Todas las marcas</option>
                    @foreach(\App\Models\Equipos::whereNull('deleted_at')->distinct()->get() as $equipo)
                        <option value="{{ $equipo->Marca }}" {{ $filtros['marca'] == $equipo->Marca ? 'selected' : '' }}>
                            {{ $equipo->Marca }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="gerencia_id">Gerencia</label>
                <select id="gerencia_id" name="gerencia_id" class="jz form-control">
                    <option value="">Todas las gerencias</option>
                    @foreach(\App\Models\Gerencia::whereNull('deleted_at')->get() as $gerencia)
                        <option value="{{ $gerencia->GerenciaID }}" {{ (string) $filtros['gerencia_id'] === (string) $gerencia->GerenciaID ? 'selected' : '' }}>
                            {{ $gerencia->NombreGerencia }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="categoria_nombre">Categoría</label>
                <select id="categoria_nombre" name="categoria_nombre" class="jz form-control">
                    <option value="">Todas las categorías</option>
                    @foreach(\App\Models\Categorias::whereNull('deleted_at')->get() as $categoria)
                        <option value="{{ $categoria->Categoria }}" {{ $filtros['categoria_nombre'] == $categoria->Categoria ? 'selected' : '' }}>
                            {{ $categoria->Categoria }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="fecha_desde">Fecha desde</label>
                <input id="fecha_desde" type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}" class="form-control">
            </div>
            <div class="form-group">
                <label for="fecha_hasta">Fecha hasta</label>
                <input id="fecha_hasta" type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}" class="form-control">
            </div>
            <div class="index-page__filters-actions">
                <button type="submit" class="index-page__btn-primary">
                    <i class="fas fa-search"></i> Aplicar filtros
                </button>
                <a href="{{ route('reportes-especificos.equipos-asignados') }}" class="index-page__btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </form>
    </x-slot>

    @include('layouts.partials.index-datatable')
</x-index-page>
@endsection
