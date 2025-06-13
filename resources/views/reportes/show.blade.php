@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Reporte: {{ $reportes->title }}</h4>

    @push('third_party_stylesheets')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    @endpush

    <div class="table-responsive mt-3">
        <table id="reporte-dinamico" class="table table-bordered table-striped table-sm w-100">
            <thead>
                <tr>
                    @foreach (array_keys((array)$resultado[0] ?? []) as $col)
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
        </table>
    </div>

    @push('third_party_scripts')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap Bundle (solo si lo necesitas) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables core -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

    <!-- Inicialización básica -->
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
</div>
@endsection