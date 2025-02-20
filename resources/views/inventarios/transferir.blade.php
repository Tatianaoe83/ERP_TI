@extends('layouts.app')

@section('content')
<section class="section">
        <div class="section-header">
            <h3 class="page__heading">Inventario de:</h3> <h5 style="margin-bottom: 6px;padding-left: 5px;">{{$inventario->NombreEmpleado}}</h5>
        </div>

    
    <div class="section-body">

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">



        <div class="card-body">
                <div class="row">
                    <form action="{{ route('inventarios.transpaso', $inventario->EmpleadoID) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                         <!-- equiposAsignados Seleccionados -->
                         <p class="lead mt-4">Equipos Asignados</p>

                        <div class="table-responsive">
                            <table id="equiposAsignadosTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>Categoria</th>
                                        <th>Marca</th>
                                        <th>Caracteristicas</th>
                                        <th>Modelo</th>
                                        <th>Precio</th>
                                        <th>Fecha Asignacion</th>
                                        <th>Fecha de Compra</th>
                                        <th>Num. Serie</th>
                                        <th>Folio</th>
                                        <th>Gerencia Equipo</th>
                                        <th>Comentarios</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($equiposAsignados as $equiposAsignado)
                                    <tr data-id="{{ $equiposAsignado->InventarioID }}">
                                        <td><input type="checkbox" class="selectItem" name="equipos[]" value="{{ $equiposAsignado->inventarioID }}"></td>
                                        <td>{{ $equiposAsignado->CategoriaEquipo }}</td>
                                        <td>{{ $equiposAsignado->Marca }}</td>
                                        <td>{{ $equiposAsignado->Caracteristicas }}</td>
                                        <td>{{ $equiposAsignado->Modelo }}</td>
                                        <td>{{ $equiposAsignado->Precio }}</td>
                                        <td>{{ $equiposAsignado->FechaAsignacion }}</td>
                                        <td>{{ $equiposAsignado->FechaDeCompra }}</td>
                                        <td>{{ $equiposAsignado->NumSerie }}</td>
                                        <td>{{ $equiposAsignado->Folio }}</td>
                                        <td data-gerencia-id="{{ $equiposAsignado->GerenciaEquipoID }}">{{ $equiposAsignado->gerenciaid->NombreGerencia }}</td>
                                        <td>{{ $equiposAsignado->Comentarios }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>


                          <!-- insumosasignados Seleccionados -->
                           <p class="lead mt-4">Insumos Asignados</p>

                            <div class="table-responsive">
                                <table id="insumosAsignadosTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>Categoria Insumo</th>
                                        <th>Nombre Insumo</th>
                                        <th>Costo Mensual</th>
                                        <th>Costo Anual</th>
                                        <th>Frecuencia de Pago</th>
                                        <th>Observaciones</th>
                                        <th>Fecha de Asignacion</th>
                                        <th>Num. Serie</th>
                                        <th>Comentarios</th>
                                        <th>Mes de pago </th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($insumosAsignados as $insumosAsignado)
                                        <tr data-id="{{ $insumosAsignado->InventarioID }}">
                                            <td><input type="checkbox" class="selectItem" name="insumos[]" value="{{ $insumosAsignado->InventarioID }}"></td>
                                            <td>{{ $insumosAsignado->CateogoriaInsumo }}</td>
                                            <td>{{ $insumosAsignado->NombreInsumo }}</td>
                                            <td>{{ $insumosAsignado->CostoMensual }}</td>
                                            <td>{{ $insumosAsignado->CostoAnual }}</td>
                                            <td>{{ $insumosAsignado->FrecuenciaDePago }}</td>
                                            <td>{{ $insumosAsignado->Observaciones }}</td>
                                            <td>{{ $insumosAsignado->FechaAsignacion }}</td>
                                            <td>{{ $insumosAsignado->NumSerie }}</td>
                                            <td>{{ $insumosAsignado->Comentarios }}</td>
                                            <td>{{ $insumosAsignado->MesDePago }}</td>
                                        </tr>
                                        @endforeach

                                    
                                    </tbody>
                                </table>
                            </div>

                            <!-- lineas asignadas-->
                            <p class="lead mt-4">Líneas Asignadas</p>
                                <div class="table-responsive">
                                    <table id="lineasAsignadosTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" id="selectAll"></th>
                                                <th>Num Telefónico</th>
                                                <th>Compañía</th>
                                                <th>Plan Tel</th>
                                                <th>Costo Renta Mensual</th>
                                                <th>Cuenta Padre</th>
                                                <th>Cuenta Hija</th>
                                                <th>Tipo Línea</th>
                                                <th>Obra</th>
                                                <th>Fecha Fianza</th>
                                                <th>Costo Fianza</th>
                                                <th>Fecha Asignación</th>
                                                <th>Estado</th>
                                                <th>Comentarios</th>
                                                <th>Monto Renovación Fianza</th>
                                                <th>Linea ID</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($LineasAsignados as $LineasAsignado)
                                            <tr data-id="{{ $LineasAsignado->InventarioID }}">
                                                <td><input type="checkbox" class="selectItem" name="lineas[]" value="{{ $LineasAsignado->LineaID }}"></td>
                                                <td>{{ $LineasAsignado->NumTelefonico }}</td>
                                                <td>{{ $LineasAsignado->Compania }}</td>
                                                <td>{{ $LineasAsignado->PlanTel }}</td>
                                                <td>{{ $LineasAsignado->CostoRentaMensual }}</td>
                                                <td>{{ $LineasAsignado->CuentaPadre }}</td>
                                                <td>{{ $LineasAsignado->CuentaHija }}</td>
                                                <td>{{ $LineasAsignado->TipoLinea }}</td>
                                                <td>{{ $LineasAsignado->Obra }}</td>
                                                <td>{{ $LineasAsignado->FechaFianza }}</td>
                                                <td>{{ $LineasAsignado->CostoFianza }}</td>
                                                <td>{{ $LineasAsignado->FechaAsignacion }}</td>
                                                <td>{{ $LineasAsignado->Estado }}</td>
                                                <td>{{ $LineasAsignado->Comentarios }}</td>
                                                <td>{{ $LineasAsignado->MontoRenovacionFianza }}</td>
                                                <td>{{ $LineasAsignado->LineaID }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>



                        <button type="submit" class="btn btn-primary">Transferir</button>
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

<script>
$(document).ready(function() {
            // Inicializar DataTables
            let table = $('#equiposAsignadosTable').DataTable({
                "paging": true,
                "lengthMenu": [5, 10, 25, 50],
                "pageLength": 5,
                "searching": true,
                "ordering": true,
                "info": true,
                
            });

                        

            let table2 = $('#insumosAsignadosTable').DataTable({
                "paging": true,
                "lengthMenu": [5, 10, 25, 50],
                "pageLength": 5,
                "searching": true,
                "ordering": true,
                "info": true,
                
            });

            let table3 = $('#lineasAsignadosTable').DataTable({
                "paging": true,
                "lengthMenu": [5, 10, 25, 50],
                "pageLength": 5,
                "searching": true,
                "ordering": true,
                "info": true,
                
            });
      });
</script>          
<script>
    document.getElementById('selectAll').addEventListener('click', function() {
        let checkboxes = document.querySelectorAll('.selectItem');
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
    });
</script>
@endpush

