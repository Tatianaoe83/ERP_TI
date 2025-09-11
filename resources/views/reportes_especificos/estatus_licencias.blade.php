@extends('layouts.app')

@section('content')

<div class="container-fluid px-3 py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                <div class="mb-3 mb-md-0">
                    <h1 class="h3 font-weight-bold text-primary mb-2">Estatus de Licencias Asignadas</h1>
                    <p class="text-muted mb-0">Reporte detallado del estado de las licencias de software asignadas</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('reportes-especificos.export-estatus-licencias', $filtros) }}" 
                       class="btn btn-success btn-sm">
                        <i class="fas fa-download me-2"></i>Descargar PDF
                    </a>
                    <a href="{{ route('reportes-especificos.index') }}" 
                       class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>Filtros de Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reportes-especificos.estatus-licencias') }}" id="filtros-form">
                <div class="row g-3">
                    <!-- Empleado -->
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label">Empleado</label>
                        <select name="empleado_id" class="form-select">
                            <option value="">Todos los empleados</option>
                            @foreach(\App\Models\Empleados::whereNull('deleted_at')->get() as $empleado)
                                <option value="{{ $empleado->EmpleadoID }}" {{ $filtros['empleado_id'] == $empleado->EmpleadoID ? 'selected' : '' }}>
                                    {{ $empleado->NombreEmpleado }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Frecuencia de Pago -->
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label">Frecuencia de Pago</label>
                        <select name="frecuencia_pago" class="form-select">
                            <option value="">Todas las frecuencias</option>
                            <option value="Pago único" {{ $filtros['frecuencia_pago'] == 'Pago único' ? 'selected' : '' }}>Pago único</option>
                            <option value="Mensual" {{ $filtros['frecuencia_pago'] == 'Mensual' ? 'selected' : '' }}>Mensual</option>
                            <option value="Anual" {{ $filtros['frecuencia_pago'] == 'Anual' ? 'selected' : '' }}>Anual</option>
                        </select>
                    </div>

                    <!-- Fecha Desde -->
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label">Fecha Desde</label>
                        <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}" class="form-control">
                    </div>

                    <!-- Fecha Hasta -->
                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label">Fecha Hasta</label>
                        <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}" class="form-control">
                    </div>

                    <!-- Botones -->
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Aplicar Filtros
                            </button>
                            <a href="{{ route('reportes-especificos.estatus-licencias') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Limpiar Filtros
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-table me-2"></i>Resultados del Reporte
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                {!! $dataTable->table(['width' => '100%', 'class' => 'table table-bordered table-striped']) !!}
            </div>
        </div>
    </div>
</div>

@push('third_party_stylesheets')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">
@endpush

@push('third_party_scripts')
<!-- DataTables Core -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>

<!-- JSZIP y PDFMake para exportación -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>

<!-- DataTables Scripts -->
{!! $dataTable->scripts() !!}
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    console.log('DataTable script loaded');
});
</script>
@endpush
@endsection
