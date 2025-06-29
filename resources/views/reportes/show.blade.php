@push('third_party_stylesheets')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
@endpush

@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Detalles del Reporte: {{ $reportes->title }}</h3>
    </div>
    <div class="section-body">
        <div class="content px-3">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        @include('reportes.show_fields')
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('reportes.index') }}" class="btn btn-danger">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('third_party_scripts')
<!-- Bootstrap Bundle (solo si no está en tu layout) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables core -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {
        $('#reporte-dinamico').DataTable({
            responsive: true,
            pageLength: 10,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            }
        });
    });
</script>
@endpush