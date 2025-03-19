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
                                                {!! Form::label('TipoFor', 'Tipo de formato:') !!} <abbr title="requerido">*</abbr>
                                                <select class="form-control" name="TipoFor" id="TipoFor" required>
                                                    <option value="">Seleccione formato</option>
                                                    <option value="1">Carta de equipos</option>
                                                    <option value="2">Carta de radios</option>
                                                    <option value="3">Carta de telefonia</option>
                                                    <option value="4">Carta de mantenimiento</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-sm-6">
                                                {!! Form::label('empresa', 'Propiedad de:') !!} <abbr title="requerido">*</abbr>
                                                {!!Form::select('empresa',App\Models\UnidadesDeNegocio::all()->
                                                    pluck('NombreEmpresa','UnidadNegocioID'),null,['placeholder' => 'Seleccionar','class'=>'form-control','required'])!!}
                                            </div>
                                                <div class="form-group col-sm-6">
                                            {!! Form::label('acomodato', 'Acomodato:') !!} <abbr title="requerido">*</abbr>

                                            <select class="form-control" name="acomodato" required>
                                                <option value="Tobra">Terminacion de obra</option>
                                                <option value="TContrato">Terminacion de contrato</option>
                                                <option value="Temp">Temporal</option>
                                                </select>
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
                                                ['placeholder' => 'Seleccionar', 'class' => 'jz form-control', 'required']
                                            ) !!}


                                        </div>
                                        
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="selectedItems" id="selectedItems">
                            
                            
                            <div class="table-responsive">
                                <table class="table table-sm" id="inventario-table">
                                    <thead>
                                        <tr id="table-header">
                                            <th><input type="checkbox" id="checkAll"></th>
                                            <th>Folio</th>
                                            <th>ID</th>
                                            <th>Descripción</th>
                                            <th>Detalles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>


                            <p>Extras </p>
                            
                            <div class="table-responsive">
                              
                                <table class="table table-sm" id="insumos-table">
                                    <thead>
                                        <tr id="table-header-insumos">
                                            <th><input type="checkbox" id="checkAllInsumos"></th>
                                            <th>Folio</th>
                                            <th>Descripción</th>
                                            <th>Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
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
    let table = $('#inventario-table').DataTable();
    let insumosTable = $('#insumos-table').DataTable();
    let editId = "{{ $id }}"; // ID del edit desde la URL

    let selectedEquipos = new Set(); // IDs de equipos seleccionados
    let selectedInsumos = new Set(); // IDs de insumos seleccionados

    $('#TipoFor').change(function() {
        let tipoId = $(this).val();
        let url = "{{ url('/inventarios/getData') }}/" + tipoId + "/" + editId;

       
         selectedEquipos.clear();
        selectedInsumos.clear();

        
        $('#checkAllEquipos').prop('checked', false);
        $('#checkAllInsumos').prop('checked', false);

        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                table.clear().draw();
                insumosTable.clear().draw();
                $('#table-header').html('');
                $('#table-header-insumos').html('');

                let headers = '';
                let dataRows = '';
                let insumosHeaders = '';
                let insumosRows = '';

                if (tipoId == 1) { // Equipos + Insumos
                    headers = `<th><input type='checkbox' id='checkAllEquipos'></th><th>Folio</th><th>Categoría</th><th>Marca</th><th>Características</th><th>Modelo</th><th>Número de Serie</th><th>Fecha de Asignación</th>`;
                    response.data.forEach(function(item, index) {
                        let isChecked = selectedEquipos.has(item.id) ? 'checked' : '';
                        dataRows += `<tr>
                            <td><input type='checkbox' class='item-check' value='${item.id}' ${isChecked}></td>
                            <td>${item.id}</td>
                            <td>${item.CategoriaEquipo}</td>
                            <td>${item.Marca}</td>
                            <td>${item.Caracteristicas}</td>
                            <td>${item.Modelo}</td>
                            <td>${item.NumSerie}</td>
                            <td>${item.FechaAsignacion}</td>
                        </tr>`;
                    });

                    insumosHeaders = `<th><input type='checkbox' id='checkAllInsumos'></th><th>Folio</th><th>Categoría</th><th>Nombre Insumo</th><th>Número Serie</th><th>Comentarios</th><th>Folio</th>`;
                    response.insumos.forEach(function(item, index) {
                        let isChecked = selectedInsumos.has(item.id) ? 'checked' : '';
                        insumosRows += `<tr>
                            <td><input type='checkbox' class='insumo-check' value='${item.id}' ${isChecked}></td>
                            <td>${index + 1}</td>
                            <td>${item.CateogoriaInsumo}</td>
                            <td>${item.NombreInsumo}</td>
                            <td>${item.NumSerie}</td>
                            <td>${item.Comentarios}</td>
                            <td><input type="text" class="form-control" name="folio_${item.id}"></td>
                        </tr>`;
                    });

                } else if (tipoId == 2) { // Radios
                    headers = `<th><input type='checkbox' id='checkAllEquipos'></th><th>Folio</th><th>Categoría</th><th>Marca</th><th>Características</th><th>Modelo</th><th>Número Serie</th><th>Fecha de Asignación</th>`;
                    response.data.forEach(function(item, index) {
                        let isChecked = selectedEquipos.has(item.id) ? 'checked' : '';
                        dataRows += `<tr>
                           <td><input type='checkbox' class='item-check' value='${item.id}' ${isChecked}></td>
                            <td>${item.id}</td>
                            <td>${item.CategoriaEquipo}</td>
                            <td>${item.Marca}</td>
                            <td>${item.Caracteristicas}</td>
                            <td>${item.Modelo}</td>
                            <td>${item.NumSerie}</td>
                            <td>${item.FechaAsignacion}</td>
                        </tr>`;
                    });
                } else if (tipoId == 3) { // Telefonía
                    headers = `<th><input type='checkbox' id='checkAllEquipos'></th><th>Folio</th><th>Numero telefono</th><th>Categoría</th><th>Marca</th><th>Caracteristicas</th><th>Modelo</th><th>Num. serie</th>`;
                    response.data.forEach(function(item, index) {
                        let isChecked = selectedEquipos.has(item.id) ? 'checked' : '';
                        dataRows += `<tr>
                            <td><input type='checkbox' class='item-check' value='${item.id}' ${isChecked}></td>
                            <td>${item.id}</td>
                            <td>${item.NumTelefonico}</td>
                            <td>${item.CategoriaEquipo}</td>
                            <td>${item.Marca}</td>
                            <td>${item.Caracteristicas}</td>
                            <td>${item.Modelo}</td>
                            <td>${item.NumSerie}</td>
                           
                        </tr>`;
                    });
                } else if (tipoId == 4) { // Mantenimiento + Insumos
                    headers = `<th><input type='checkbox' id='checkAllEquipos'></th><th>Folio</th><th>Categoría</th><th>Marca</th><th>Características</th><th>Modelo</th><th>Número de Serie</th><th>Fecha de Asignación</th>`;
                    response.data.forEach(function(item, index) {
                        let isChecked = selectedEquipos.has(item.id) ? 'checked' : '';
                        dataRows += `<tr>
                            <td><input type='checkbox' class='item-check' value='${item.id}' ${isChecked}></td>
                            <td>${item.id}</td>
                            <td>${item.CategoriaEquipo}</td>
                            <td>${item.Marca}</td>
                            <td>${item.Caracteristicas}</td>
                            <td>${item.Modelo}</td>
                            <td>${item.NumSerie}</td>
                            <td>${item.FechaAsignacion}</td>
                        </tr>`;
                    });

                }
                
                $('#table-header').html(headers);
                $('#table-header-insumos').html(insumosHeaders);
                table.rows.add($(dataRows)).draw();
                insumosTable.rows.add($(insumosRows)).draw();
            }
        });
    });

    // Manejar selección de equipos
    $(document).on('change', '.item-check', function() {
        let itemId = $(this).val();
        if (this.checked) {
            selectedEquipos.add(itemId);
        } else {
            selectedEquipos.delete(itemId);
        }
    });

    // Manejar selección de insumos
    $(document).on('change', '.insumo-check', function() {
        let itemId = $(this).val();
        if (this.checked) {
            selectedInsumos.add(itemId);
        } else {
            selectedInsumos.delete(itemId);
        }
    });

    // Manejar selección general de equipos
    $(document).on('change', '#checkAllEquipos', function() {
        let isChecked = this.checked;
        $('.item-check').each(function() {
            $(this).prop('checked', isChecked);
            let itemId = $(this).val();
            if (isChecked) {
                selectedEquipos.add(itemId);
            } else {
                selectedEquipos.delete(itemId);
            }
        });
    });

    // Manejar selección general de insumos
    $(document).on('change', '#checkAllInsumos', function() {
        let isChecked = this.checked;
        $('.insumo-check').each(function() {
            $(this).prop('checked', isChecked);
            let itemId = $(this).val();
            if (isChecked) {
                selectedInsumos.add(itemId);
            } else {
                selectedInsumos.delete(itemId);
            }
        });
    });

    // Restaurar selección al cambiar de página en DataTables
    table.on('draw', function() {
        $('.item-check').each(function() {
            let itemId = $(this).val();
            $(this).prop('checked', selectedEquipos.has(itemId));
        });
    });

    insumosTable.on('draw', function() {
        $('.insumo-check').each(function() {
            let itemId = $(this).val();
            $(this).prop('checked', selectedInsumos.has(itemId));
        });
    });

    // Antes de enviar el formulario, pasamos los valores seleccionados
    $('#formulario').submit(function(event) {
        $('#selectedItems').val([...selectedEquipos].join(',') + '|' + [...selectedInsumos].join(',')); 
    });
});

</script>
@endpush
