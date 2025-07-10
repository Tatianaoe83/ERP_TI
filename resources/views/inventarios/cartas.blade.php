@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Cartas de entrega de: </h3> <h5 style="margin-bottom: 6px;padding-left: 5px;">{{$empleado->NombreEmpleado}}</h5>
    </div>

    <div class="section-body">
        <div class="content px-3">
            @include('adminlte-templates::common.errors')

            <div class="row">
            <div class="col-12 col-sm-12 col-lg-12">
                <div class="card">
                  <div class="card-header">
                    <h4>Mantenimiento preventivo</h4>
                  </div>
                  <div class="card-body">
                    
                    <form id="formulario2" action="{{ route('inventarios.mantenimiento', $id) }}" method="POST" target="_blank">
                        @csrf

                    <div class="row">
                        <div class="col-12 col-sm-12 col-lg-12" style="margin-bottom: 12px;">
                        {!! Form::label('IdEquipo', 'Seleccione el equipo:') !!}
                        
                        {!!Form::select('IdEquipo',App\Models\InventarioEquipo::select(DB::raw("CONCAT(Folio,' - ', CategoriaEquipo) AS NombreEq, InventarioID"))
                            ->where('EmpleadoID', '=', $id)
                            ->pluck('NombreEq','InventarioID'),null,['placeholder' => 'Seleccionar','class'=>'jz form-control','style' => 'width: 100%'])!!}
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-12 col-sm-12 col-lg-12" style="margin-bottom: 12px;">
                            <button type="button" class="btn btn-sm btn-warning" id="selectAll">Seleccionar todos</button>
                        </div>
                    </div>

                        
                    <div class="row">

                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='1' id="defaultCheck1">
                                <label class="form-check-label" for="defaultCheck1">
                                    Desarme y ensamble de equipo
                                </label>
                            </div>
                        </div>
                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='2' id="defaultCheck2">
                                <label class="form-check-label" for="defaultCheck2">
                                    Formateo e instalación del sistema operativo
                                </label>
                            </div>
                        </div>
                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]"  value='3'  id="defaultCheck3">
                                <label class="form-check-label" for="defaultCheck3">
                                    Limpieza interna
                                </label>
                            </div>
                        </div>
                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='4'  id="defaultCheck4">
                                <label class="form-check-label" for="defaultCheck4">
                                    Respaldo de información
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        
                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='6'  id="defaultCheck6">
                                <label class="form-check-label" for="defaultCheck6">
                                Cambio de pasta térmica
                                </label>
                            </div>
                        </div>
                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='7' id="defaultCheck7">
                                <label class="form-check-label" for="defaultCheck7">
                                    Limpieza de periféricos
                                </label>
                            </div>
                        </div>
                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='8'  id="defaultCheck8">
                                <label class="form-check-label" for="defaultCheck8">
                                    Actualizaciones de software
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='9' id="defaultCheck9">
                                <label class="form-check-label" for="defaultCheck9">
                                    Eliminación de temporales
                                </label>
                            </div>
                        </div>
                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='10' id="defaultCheck10">
                                <label class="form-check-label" for="defaultCheck10">
                                    Limpieza de ventiladores
                                </label>
                            </div>
                        </div>
                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='11' id="defaultCheck11">
                                <label class="form-check-label" for="defaultCheck11">
                                    Limpieza de fuente de poder
                                </label>
                            </div>
                        </div>
                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='12' id="defaultCheck12">
                                <label class="form-check-label" for="defaultCheck12">
                                    Instalación de software por licencia
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        
                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='14' id="defaultCheck14">
                                <label class="form-check-label" for="defaultCheck14">
                                    Limpieza del teclado
                                </label>
                            </div>
                        </div>
                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='15' id="defaultCheck15">
                                <label class="form-check-label" for="defaultCheck15">
                                    Cambio de piezas
                                </label>
                            </div>
                        </div>
                        <div class="col-3 col-sm-3 col-lg-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='16' id="defaultCheck16">
                                <label class="form-check-label" for="defaultCheck16">
                                    Cambio de pasta térmica en la tarjeta grafica
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-sm-12 col-lg-12">
                        <div class="form-check">
                                <input class="form-check-input" type="checkbox"  name="inventarioPreven[]" value='17' id="defaultCheck17">
                                <label class="form-check-label" for="defaultCheck17">
                                    Cambio de equipo de computo
                                </label>
                            </div>

                        </div>
                    </div>
                    


                        
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" >Generar formato</button>
                    </div>
                    </form>
                  </div>
                </div>
               
              </div>
            </div>


            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <form id="formulario" action="{{ route('inventarios.pdffile', $id) }}" method="POST" target="_blank">
                            @csrf
                            
                            
                           
                            
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
                            <a href="{{ route('inventarios.index') }}" class="btn btn-danger">Cancelar</a>
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

<script>
    document.getElementById('selectAll').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('input[name="inventarioPreven[]"]');
        const isChecked = checkboxes[0].checked;
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = !isChecked;
        });
        
        this.textContent = isChecked ? 'Seleccionar todos' : 'Deseleccionar todos';
    });
</script>


@endpush
