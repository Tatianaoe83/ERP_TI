@extends('layouts.app')

@section('content')
<section class="section">
    <div class="flex flex-row gap-2 items-center">
        <h3 class="dark:bg-[#101010] dark:text-white">Inventario de:</h3>
        <h5 class="dark:bg-[#101010] dark:text-white">{{$inventario->NombreEmpleado}}</h5>
    </div>


    <div class="section-body">

        <div class="content px-3">

            @include('adminlte-templates::common.errors')

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
                                        <th><input type="checkbox" class="selectAll" data-table="equiposAsignadosTable"></th>
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
                                <tbody class="!table-black">
                                    @foreach ($equiposAsignados as $equiposAsignado)
                                    <tr data-id="{{ $equiposAsignado->InventarioID }}">
                                        <td><input type="checkbox" class="selectItem" name="equipos[]" value="{{ $equiposAsignado->InventarioID}}"></td>
                                        <td>{{ $equiposAsignado->CategoriaEquipo }}</td>
                                        <td>{{ $equiposAsignado->Marca }}</td>
                                        <td>{{ $equiposAsignado->Caracteristicas }}</td>
                                        <td>{{ $equiposAsignado->Modelo }}</td>
                                        <td>{{ $equiposAsignado->Precio }}</td>
                                        <td>{{ $equiposAsignado->FechaAsignacion }}</td>
                                        <td>{{ $equiposAsignado->FechaDeCompra }}</td>
                                        <td>{{ $equiposAsignado->NumSerie }}</td>
                                        <td>{{ $equiposAsignado->Folio }}</td>
                                        <td data-gerencia-id="{{ $equiposAsignado->GerenciaEquipoID }}">{{ $equiposAsignado->gerencia->NombreGerencia ?? 'Sin Gerencia' }}</td>
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
                                        <th><input type="checkbox" class="selectAll" data-table="insumosAsignadosTable"></th>
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
                                        <th><input type="checkbox" class="selectAll" data-table="lineasAsignadosTable"></th>
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
                                        <td><input type="checkbox" class="selectItem" name="lineas[]" value="{{ $LineasAsignado->InventarioID }}"></td>
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



                        <button type="submit" class="btn btn-primary show_confirm">Transferir</button>
                        <a href="{{ route('inventarios.index') }}" class="btn btn-danger">Regresar</a>
                    </form>
                </div>
            </div>


            <div class="card-footer">
            </div>



        </div>
</section>

@endsection

@push('third_party_scripts')

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {

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

        // Select All Checkbox functionality
        $('.selectAll').click(function() {
            var tableId = $(this).data('table');
            $('#' + tableId + ' input[type="checkbox"]').prop('checked', $(this).prop('checked'));
        });
    });



    $('.show_confirm').click(function(event) {
        var form = $(this).closest("form");
        event.preventDefault();

        var empleadosOptions = '';
        @foreach($Empleados as $empleado)
        empleadosOptions += `<option value="{{$empleado->EmpleadoID}}">{{$empleado->NombreEmpleado}}</option>`;
        @endforeach

        swal.fire({
            title: `¿Está seguro de que desea realizar esta acción?`,
            icon: "warning",
            html: `
            <label for="empleado" class="dark:text-white">Selecciona un empleado:</label>
            <select id="empleado" class="dark:bg-[#101010] dark:text-white">
                <option value="">--Seleccione un empleado--</option>
                ${empleadosOptions}
            </select>
        `,
            didOpen: () => {
                $('#empleado').select2({
                    dropdownParent: $('.swal2-popup'),
                    width: '100%',

                });

                $('.swal2-popup').addClass('dark:bg-[#101010]');
                
                // Aplicar estilos para modo oscuro en select2
                setTimeout(() => {
                    $('.select2-search__field').addClass('dark:bg-[#101010] dark:text-white dark:placeholder-gray-400');
                    $('.select2-results__option').addClass('dark:bg-[#101010] dark:text-white');
                    $('.select2-dropdown').addClass('dark:bg-[#101010]');
                }, 100);
            },
            showDenyButton: true,
            confirmButtonText: 'Confirmar',
            denyButtonText: 'Cerrar',
            dangerMode: true,
        }).then(function(result) {
            var selectedEmpleado = $('#empleado').val();
            if (result.isConfirmed) {
                if (!selectedEmpleado) {
                    swal.fire({
                        title: '¡Debes seleccionar un empleado!',
                        icon: 'error',
                        didOpen: () => {
                            $('.swal2-popup').addClass('dark:bg-[#101010] dark:text-white');
                            $('.swal2-title').addClass('dark:text-white');
                        }
                    });
                } else {
                    swal.fire({
                        title: 'Acción completada exitosamente',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000,
                        didOpen: () => {
                            $('.swal2-popup').addClass('dark:bg-[#101010] dark:text-white');
                            $('.swal2-title').addClass('dark:text-white');
                        }
                    }).then(function() {
                        form.append('<input type="hidden" name="empleado_id" value="' + selectedEmpleado + '">');
                        form.submit();
                    });
                }
            } else if (result.isDenied) {
                swal.fire({
                    title: 'Cambios no realizados',
                    icon: 'error',
                    showConfirmButton: false,
                    timer: 2000,
                    didOpen: () => {
                        $('.swal2-popup').addClass('dark:bg-[#101010] dark:text-white');
                        $('.swal2-title').addClass('dark:text-white');
                    }
                });
            }
        });
    });
</script>

<style>
/* Estilos para modo oscuro en select2 */
.dark .select2-search__field {
    background-color: #101010 !important;
    color: white !important;
}

.dark .select2-search__field::placeholder {
    color: #9ca3af !important;
}

.dark .select2-results__option {
    background-color: #101010 !important;
    color: white !important;
}

.dark .select2-results__option--highlighted {
    background-color: #3b82f6 !important;
    color: white !important;
}

.dark .select2-dropdown {
    background-color: #101010 !important;
    border-color: #374151 !important;
}

.dark .select2-container--default .select2-selection--single {
    background-color: #101010 !important;
    border-color: #374151 !important;
    color: white !important;
}

.dark .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: white !important;
}

.dark .select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #9ca3af !important;
}
</style>

@endpush