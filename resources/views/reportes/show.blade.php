@push('third_party_stylesheets')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
@endpush

@extends('layouts.app')

@section('content')
<h3 class="space-x-1 dark:bg-[#101010] dark:text-white">Detalles del Reporte: {{ $reportes->title }}</h3>
<div class="content px-3">
    <div class="row">
        @include('reportes.show_fields')
    </div>
    <div class="card-footer">
        <a href="{{ route('reportes.index') }}" class="btn btn-danger">Cancelar</a>
    </div>
</div>
@endsection

@push('third_party_scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function () {
    const columnas = @json($columnas);

    const columnDefs = columnas.map(function (col) {
        const key = col.includes('.') ? col.split('.').pop() : col;
        return {
            data: key,
            title: key,
            defaultContent: ''
        };
    });

    $('#reporte-dinamico').DataTable({
        processing : true,
        serverSide : true,
        ajax       : '{{ route("reportes.data", $reportes->id) }}',
        columns    : columnDefs,
        pageLength : 25,
        lengthMenu : [[25, 50, 100, 250], [25, 50, 100, 250]],
        order      : [[0, 'asc']],
        responsive : true,
        language   : {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        columnDefs: [{
            targets: '_all',
            createdCell: function (td) {
                $(td).addClass('dark:bg-[#101010] dark:text-white');
            }
        }]
    });
});
</script>
@endpush