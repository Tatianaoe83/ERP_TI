@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            
        </div>
    </section>

    <div class="content px-3">
        @include('flash::message')
        <div class="clearfix"></div>

        <div class="card">
            <div class="card-body">
            <div class="col-xs-12 col-sm-12 col-md-12">
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informe</h3>
        </div>
        <div class="card-body">
          

            <div class="table-responsive">
            <table id="auditTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Responsable del cambio</th>
                            <th>Tabla</th>
                            <th>Num. registro</th>
                            <th>Antiguos valores</th>
                            <th>Nuevos valores</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Responsable del cambio</th>
                            <th>Tabla</th>
                            <th>Num. registro</th>
                            <th>Antiguos valores</th>
                            <th>Nuevos valores</th>
                            <th>Fecha</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($audits as $audit)
                            <tr>
                                <td>{{ $audit->id }}</td>
                                <td>{{ $audit->name }}</td>
                                <td>{{ $audit->auditable_type }}</td>
                                <td>{{ $audit->auditable_id }}</td>
                                <td>{{ $audit->old_values }}</td>
                                <td>{{ $audit->new_values }}</td>
                                <td>{{ $audit->created_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>
@endsection

@push('third_party_scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

            <script>
                $(document).ready(function () {
                  
                    $('#auditTable tfoot th').each(function () {
                        var title = $(this).text();
                        $(this).html('<select class="form-control"><option value="">ALL</option></select>');
                    });

                    var table = $('#auditTable').DataTable();

                    // Añadir opciones únicas a los selects
                    table.columns().every(function () {
                        var column = this;
                        var select = $('select', column.footer());

                        column.data().unique().sort().each(function (d, j) {
                            if (d && select.find('option[value="' + d + '"]').length === 0) {
                                select.append('<option value="' + d + '">' + d + '</option>');
                            }
                        });

                        // Filtro en cambio
                        select.on('change', function () {
                            var val = $.fn.dataTable.util.escapeRegex($(this).val());
                            column.search(val ? '^' + val + '$' : '', true, false).draw();
                        });
                    });
                });
            </script>
              
@endpush