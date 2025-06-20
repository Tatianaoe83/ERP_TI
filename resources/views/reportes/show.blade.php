@push('third_party_stylesheets')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
@endpush

@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h4 class="mb-4 mt-4">Reporte: <strong>{{ $reportes->title }}</strong></h4>

    <div class="table-responsive">
        <table id="reporte-dinamico" class="table table-bordered table-striped table-sm w-100">
            @if (count($resultado) > 0)
            <thead>
                <tr>
                    @foreach (array_keys((array)$resultado[0]) as $col)
                    <th>{{ ucfirst($col) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($resultado as $fila)
                <tr>
                    @foreach ((array)$fila as $valor)
                    <td>{{ $valor }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
            @else
            <tr>
                <td colspan="100%" class="text-center text-muted">No se encontraron datos para este reporte.</td>
            </tr>
            @endif
        </table>
    </div>
</div>
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