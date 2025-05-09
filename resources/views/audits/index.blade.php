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

                <div class="row mb-3">
                <div class="col-xs-3 col-sm-3 col-md-3">
                    <div class="form-group">
                    <label>Responsable del cambio:</label>
                    {!! Form::select('user_type', App\Models\User::whereIn('id',$usuarios)
                        ->pluck('name','id'), null, ['placeholder' => 'Seleccionar', 'class'=>'jz form-control', 'required', 'id' => 'user_type']) !!}

                    </div>

                </div>

                
                <div class="col-xs-3 col-sm-3 col-md-3">
                    <div class="form-group">
                    <label>Tabla:</label>
                 
                    {!! Form::select('auditable_type', App\Models\Audit::whereIn('auditable_type',$tablas)
                        ->pluck('auditable_type','auditable_type'), null, ['placeholder' => 'Seleccionar', 'class'=>'jz form-control', 'required', 'id' => 'auditable_type']) !!}

                    </div>
                </div>
                <div class="col-xs-3 col-sm-3 col-md-3">
                    <div class="form-group">
                    <label>Valores:</label>
                    <input type="text" id="new_values" class="form-control">
                    </div>

                </div>
                @can('buscar-informe')
                <div class="col-xs-3 col-sm-3 col-md-3">
                    <button id="searchBtn" class="btn btn-primary w-100">Buscar</button>
                </div>
                @endcan

            </div>

          

            <div class="table-responsive">
            <table class="table table-bordered" id="auditsTable">
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
                <tbody></tbody>
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
                   let table;

                    $(document).ready(function () {
                      
                        table = $('#auditsTable').DataTable({
                            processing: true,
                            serverSide: true,
                            searching: false,
                            paging: true,
                            ajax: {
                                url: "{{ route('audits.data') }}",
                                data: function (d) {
                                    d.user_type = $('#user_type').val();
                                    d.auditable_type = $('#auditable_type').val();
                                    d.new_values = $('#new_values').val();
                                }
                            },
                            columns: [
                                { data: 'id' },
                                { data: 'name' },
                                { data: 'auditable_type' },
                                { data: 'auditable_id' },
                                { data: 'old_values' },
                                { data: 'new_values' },
                                { data: 'created_at' }
                            ],
                            deferLoading: 0 
                        });

                      
                        $('#searchBtn').on('click', function () {
                            table.ajax.reload();
                        });
                    });
            </script>
              
@endpush