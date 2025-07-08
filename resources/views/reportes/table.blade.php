@extends('layouts.app')

@section('content')
<div class="col-xs-12 col-sm-12 col-md-12">

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Lista de Reportes</h3>
            @can('reportes.create')
            <a href="{{ route('reportes.create') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-plus me-1"></i> Nuevo Reporte
            </a>
            @endcan
        </div>

        <div class="card-body">
            @push('third_party_stylesheets')
            <!-- css -->
            <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
            @endpush

            <div class="table-responsive">
                {!! $dataTable->table(['width' => '100%', 'class' => 'table table-bordered table-striped']) !!}
            </div>

            @push('third_party_scripts')
            <!-- Bootstrap -->

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

            <!-- DataTables Core -->
            <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

            <!-- DataTables Scripts -->
            {!! $dataTable->scripts() !!}
            @endpush
        </div>
    </div>
</div>
@endsection