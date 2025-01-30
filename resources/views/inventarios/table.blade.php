@extends('layouts.app')

@section('content')
<div class="col-xs-12 col-sm-12 col-md-12">
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Empleados</h3>
        </div>
        <div class="card-body">
            @push('third_party_stylesheets')
                <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css" rel="stylesheet">
                <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css" rel="stylesheet">
            @endpush

            <div class="table-responsive">
                {!! $dataTable->table(['width' => '100%', 'class' => 'table table-bordered table-striped']) !!}
            </div>

            @push('third_party_scripts')
                <!-- jQuery primero -->
                <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
                
                <!-- Bootstrap -->
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
                
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
        </div>
    </div>
</div>
@endsection