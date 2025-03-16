@extends('layouts.app')

@section('content')
<section class="section">
        <div class="section-header">
            <h3 class="page__heading">Cartas de entrega</h3>
        </div>

    
    <div class="section-body">

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">



        <div class="card-body">
                <div class="row">
                    <form action="{{ route('inventarios.pdffile', $id) }}" method="POST">
                        @csrf
                        @method('POST')
                        
                        
                        <div class="card">
                            <div class="card-header">
                                <h4>Datos del formato</h4>
                                <div class="card-header-action">
                                <a data-collapse="#mycard-collapse" class="btn btn-icon btn-info" href="#"><i class="fas fa-minus"></i></a>
                                </div>
                            </div>
                            <div class="collapse show" id="mycard-collapse" style="">
                                <div class="card-body">

                                <div class="row">

                                     <div class="form-group col-sm-6">
                                        {!! Form::label('TipoFor', 'Tipo de formato:') !!}

                                            <select class="form-control" name="TipoFor">
                                            <option value="Cequipos">Carta de equipos</option>
                                            <option value="Cradios">Carta de radios</option>
                                            <option value="Ctelefonia">Carta de telefonia</option>
                                            <option value="Cmantenimiento">Carta de mantenimiento</option>
                                            </select>

                                    </div>

                                    <div class="form-group col-sm-6">
                                        {!! Form::label('empresa', 'Empresa:') !!}

                                        {!!Form::select('empresa',App\Models\UnidadesDeNegocio::all()->
                                            pluck('NombreEmpresa','UnidadNegocioID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}

                                    </div>



                                    <div class="form-group col-sm-6">
                                        {!! Form::label('acomodato', 'Acomodato:') !!}

                                        <select class="form-control" name="acomodato">
                                            <option value="Tobra">Terminacion de obra</option>
                                            <option value="TContrato">Terminacion de contrato</option>
                                            <option value="Temp">Temporal</option>
                                            </select>
                                    </div>

                                    <div class="form-group col-sm-6">
                                        {!! Form::label('tiempo', 'Tiempo:') !!}
                                        <input type="text" class="form-control" name="tiempo">

                                    </div>

                                    <div class="form-group col-sm-6">
                                        {!! Form::label('telefono', 'Numero de contacto:') !!}
                                        <input type="text" class="form-control phone-number" name="telefono">

                                    </div>


                                    <div class="form-group col-sm-6">
                                        {!! Form::label('entrega', 'Persona que entrega:') !!}

                                        {!!Form::select('entrega',App\Models\Empleados::all()->
                                            pluck('NombreEmpleado','EmpleadoID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control'])!!}

                                    </div>

                                </div>
                             </div>
                                <div class="card-footer">
                                
                                </div>
                            </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <th scope="row">1</th>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">2</th>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">3</th>
                                        <td>Larry</td>
                                        <td>the Bird</td>
                                        <td>@twitter</td>
                                    </tr>
                                    </tbody>
                                </table>
                                </div>



                        <button type="submit" class="btn btn-primary">Generar</button>
                    </form>
                </div>
            </div>
         

            <div class="card-footer">
            </div>
   

        </div>
    </div>
    </section>
 
@endsection

@push('third_party_stylesheets')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
@endpush

@push('third_party_scripts')

 <!-- DataTables JS -->
 <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cleave.js@1.6.0/dist/cleave.min.js"></script>

<script>

var cleave = new Cleave('.phone-number', {
    phone: true,
    phoneRegionCode: 'us'
});


$(document).ready(function() {




})

</script>          

@endpush

