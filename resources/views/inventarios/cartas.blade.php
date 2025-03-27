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
                        <form id="formulario" action="{{ route('inventarios.pdffile', $id) }}" method="POST" target="_blank">
                            @csrf
                            <div class="card">
                                <div class="card-header">
                                    <h4>Datos del formato</h4>
                                    <div class="card-header-action">
                                        <a data-collapse="#mycard-collapse" class="btn btn-icon btn-info" href="#">
                                            <i class="fas fa-minus"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="collapse show" id="mycard-collapse">
                                    <div class="card-body">
                                        <div class="row">
                                           
                                            <div class="form-group col-sm-6">
                                                {!! Form::label('empresa', 'Propiedad de:') !!} <abbr title="requerido">*</abbr>
                                                {!!Form::select('empresa',App\Models\UnidadesDeNegocio::all()->
                                                    pluck('NombreEmpresa','UnidadNegocioID'),null,['placeholder' => 'Seleccionar','class'=>'form-control','required'])!!}
                                            </div>
                                       

                                        <div class="form-group col-sm-6">
                                                {!! Form::label('ubiequi', 'Obra/ubicacion aquipo:') !!} <abbr title="requerido">*</abbr>
                                                {!!Form::select('ubiequi',App\Models\UnidadesDeNegocio::all()->
                                                    pluck('NombreEmpresa','UnidadNegocioID'),null,['placeholder' => 'Seleccionar','class'=>'form-control','required'])!!}
                                            </div>

                                        <div class="form-group col-sm-6"> <abbr title="requerido">*</abbr>
                                            {!! Form::label('telefono', 'Número de contacto:') !!}
                                            <input type="text" class="form-control phone-number" name="telefono" 
                                                pattern="[0-9]{10}" maxlength="10" required
                                                title="Debe ingresar exactamente 10 dígitos numéricos">
                                        </div>



                                        <div class="form-group col-sm-6">
                                            {!! Form::label('entrega', 'Persona que entrega:') !!} <abbr title="requerido">*</abbr>

                                            {!! Form::select('entrega', 
                                                App\Models\Empleados::where('ObraID', 46)->pluck('NombreEmpleado', 'EmpleadoID'), 
                                                null, 
                                                ['placeholder' => 'Seleccionar', 'class' => 'jz form-control', 'required','style'=> 'width:100%;']
                                            ) !!}


                                        </div>
                                        
                                        </div>
                                    </div>
                                </div>
                            </div>
                           
                            
                        <div class="table-responsive">
                         
           
                        <table class="table table-sm" id="inventarioTable">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="checkAll"></th>
                                        <th>ID</th>
                                        <th>Categoría</th>
                                        <th>Marca/Nombre</th>
                                        <th>Características</th>
                                        <th>Modelo</th>
                                        <th>Número de Serie</th>
                                        <th>Fecha Asignación / Comentarios</th>
                                        <th>Tipo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inventario as $item)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="inventarioSeleccionado[]" value="{{ $item->id }}|{{ $item->tipo }}">
                                        </td>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->categoria }}</td>
                                        <td>{{ $item->Marca }}</td>
                                        <td>{{ $item->Caracteristicas ?? 'N/A' }}</td>
                                        <td>{{ $item->Modelo ?? 'N/A' }}</td>
                                        <td>{{ $item->NumSerie }}</td>
                                        <td>{{ $item->FechaAsignacion ?? 'N/A' }}</td>
                                        <td>
                                            @if ($item->tipo == 'EQUIPO')
                                                <span style="color: blue; font-weight: bold;">Equipo</span>
                                            @elseif ($item->tipo == 'INSUMO')
                                                <span style="color: green; font-weight: bold;">Insumo</span>
                                            @elseif ($item->tipo == 'TELEFONO')
                                                <span style="color: red; font-weight: bold;">Teléfono</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </div>



                         
                            <button type="submit" class="btn btn-primary" >Generar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('third_party_scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/cleave.js@1.6.0/dist/cleave.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Cleave('.phone-number', {
        numericOnly: true,
        blocks: [10] 
    });
});
</script>

<script>
    $(document).ready(function() {
        // Inicializar DataTables
        let table = $('#inventarioTable').DataTable();

        // Check All Functionality
        $("#checkAll").click(function() {
            $("input[name='inventarioSeleccionado[]']").prop('checked', this.checked);
        });

        
    });
</script>

@endpush
