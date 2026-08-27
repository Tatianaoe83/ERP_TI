@extends('layouts.app')

@section('content')
<x-index-page
    title="Licencias asignadas"
    icon="fa-certificate"
    subtitle="0 registros"
>
    <x-slot name="headerActions">
        @can('exportar-reportes-especificos')
        <a href="{{ route('reportes-especificos.export-estatus-licencias-excel', $filtros) }}" class="index-page__btn-primary js-excel-download" data-download-label="Excel">
            <i class="fas fa-file-excel"></i> Descargar Excel
        </a>
        @endcan
        <a href="{{ route('reportes-especificos.index') }}" class="index-page__btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </x-slot>

    <x-slot name="filters">
        <form method="GET" action="{{ route('reportes-especificos.estatus-licencias') }}">
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
                <label for="frecuencia_pago">Frecuencia de pago</label>
                <select id="frecuencia_pago" name="frecuencia_pago" class="jz form-control">
                    <option value="">Todas las frecuencias</option>
                    <option value="Pago único" {{ $filtros['frecuencia_pago'] == 'Pago único' ? 'selected' : '' }}>Pago único</option>
                    <option value="Mensual" {{ $filtros['frecuencia_pago'] == 'Mensual' ? 'selected' : '' }}>Mensual</option>
                    <option value="Anual" {{ $filtros['frecuencia_pago'] == 'Anual' ? 'selected' : '' }}>Anual</option>
                </select>
            </div>
            <div class="form-group">
                <label for="inventarioinsumo_mes_pago">Mes de pago</label>
                <select id="inventarioinsumo_mes_pago" name="inventarioinsumo_mes_pago" class="jz form-control">
                    <option value="">Todos los meses</option>
                    @php
                        $mesesLic = \App\Models\InventarioInsumo::select('MesDePago')->distinct();
                        \App\Models\PresupuestoConfiguracion::aplicarWhereIn($mesesLic, 'CateogoriaInsumo', 'licencias');
                    @endphp
                    @foreach($mesesLic->get() as $mes)
                        <option value="{{ $mes->MesDePago }}" {{ $filtros['inventarioinsumo_mes_pago'] == $mes->MesDePago ? 'selected' : '' }}>
                            {{ $mes->MesDePago }}
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
                <a href="{{ route('reportes-especificos.estatus-licencias') }}" class="index-page__btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </form>
    </x-slot>

    @include('layouts.partials.index-datatable')
</x-index-page>
@endsection
