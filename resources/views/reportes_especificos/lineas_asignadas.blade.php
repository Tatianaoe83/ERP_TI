@extends('layouts.app')

@section('content')
<x-index-page
    title="Líneas asignadas"
    icon="fa-phone"
    subtitle="0 registros"
>
    <x-slot name="headerActions">
        @can('exportar-reportes-especificos')
        <a href="{{ route('reportes-especificos.export-lineas-asignadas-excel', $filtros) }}" class="index-page__btn-primary">
            <i class="fas fa-file-excel"></i> Descargar Excel
        </a>
        @endcan
        <a href="{{ route('reportes-especificos.index') }}" class="index-page__btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </x-slot>

    <x-slot name="filters">
        <form method="GET" action="{{ route('reportes-especificos.lineas-asignadas') }}">
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
                <label for="cuenta_padre">Cuenta padre</label>
                <select id="cuenta_padre" name="cuenta_padre" class="jz form-control">
                    <option value="">Todas las cuentas padre</option>
                    @foreach(\App\Models\InventarioLineas::select('CuentaPadre')->distinct()->get() as $cuentapadre)
                        <option value="{{ $cuentapadre->CuentaPadre }}" {{ $filtros['cuenta_padre'] == $cuentapadre->CuentaPadre ? 'selected' : '' }}>
                            {{ $cuentapadre->CuentaPadre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="linea_id">Línea</label>
                <select id="linea_id" name="linea_id" class="jz form-control">
                    <option value="">Todas las líneas</option>
                    @foreach(\App\Models\LineasTelefonicas::whereNull('deleted_at')->get() as $linea)
                        <option value="{{ $linea->LineaID }}" {{ (string) $filtros['linea_id'] === (string) $linea->LineaID ? 'selected' : '' }}>
                            {{ $linea->NumTelefonico }} - {{ $linea->TipoLinea }}
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
                <a href="{{ route('reportes-especificos.lineas-asignadas') }}" class="index-page__btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </form>
    </x-slot>

    @include('layouts.partials.index-datatable')
</x-index-page>
@endsection
