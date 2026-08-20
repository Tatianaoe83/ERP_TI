@extends('layouts.app')

@section('content')
@include('flash::message')
@include('adminlte-templates::common.errors')

@php
    $nEquipos = $equiposAsignados->count();
    $nInsumos = $insumosAsignados->count();
    $nLineas = $LineasAsignados->count();
@endphp

<x-index-page
    title="Transferir inventario"
    icon="fa-exchange-alt"
    :subtitle="$inventario->NombreEmpleado"
    :show-count="true"
    :card="false"
>
    <x-slot name="headerActions">
        <a href="{{ route('inventarios.index') }}" class="index-page__btn-secondary">Regresar</a>
    </x-slot>

    <p class="text-sm text-gray-500 dark:text-gray-400" style="margin: -0.35rem 0 1.1rem;">
        Marca los equipos, insumos o líneas que deseas pasar a otro empleado.
    </p>

    <form action="{{ route('inventarios.transpaso', $inventario->EmpleadoID) }}" method="POST" id="form-transferir">
        @csrf
        @method('PUT')

        <div class="index-page__card overflow-hidden xfer-card">
            <div class="xfer-card-head">
                <div>
                    <h2>Equipos asignados</h2>
                    <span class="index-page__count xfer-card-count">{{ $nEquipos === 1 ? '1 registro' : $nEquipos . ' registros' }}</span>
                </div>
            </div>
            <div class="index-page__table-wrap table-responsive">
                <table id="equiposAsignadosTable" class="table index-table w-full">
                    <thead>
                        <tr>
                            <th class="xfer-check-col"><input type="checkbox" class="selectAll xfer-check" data-table="equiposAsignadosTable" title="Seleccionar todos"></th>
                            <th>Categoría</th>
                            <th>Marca</th>
                            <th>Características</th>
                            <th>Modelo</th>
                            <th>Precio</th>
                            <th>Fecha asignación</th>
                            <th>Fecha de compra</th>
                            <th>Núm. serie</th>
                            <th>Folio</th>
                            <th>Gerencia equipo</th>
                            <th>Comentarios</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($equiposAsignados as $equiposAsignado)
                        <tr data-id="{{ $equiposAsignado->InventarioID }}">
                            <td><input type="checkbox" class="selectItem xfer-check" name="equipos[]" value="{{ $equiposAsignado->InventarioID }}"></td>
                            <td>{{ $equiposAsignado->CategoriaEquipo }}</td>
                            <td>{{ $equiposAsignado->Marca }}</td>
                            <td>{{ $equiposAsignado->Caracteristicas }}</td>
                            <td>{{ $equiposAsignado->Modelo }}</td>
                            <td>{{ $equiposAsignado->Precio }}</td>
                            <td>{{ $equiposAsignado->FechaAsignacion }}</td>
                            <td>{{ $equiposAsignado->FechaDeCompra }}</td>
                            <td>{{ $equiposAsignado->NumSerie }}</td>
                            <td>{{ $equiposAsignado->Folio }}</td>
                            <td data-gerencia-id="{{ $equiposAsignado->GerenciaEquipoID }}">{{ $equiposAsignado->gerencia->NombreGerencia ?? 'Sin gerencia' }}</td>
                            <td>{{ $equiposAsignado->Comentarios }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="index-page__card overflow-hidden xfer-card">
            <div class="xfer-card-head">
                <div>
                    <h2>Insumos asignados</h2>
                    <span class="index-page__count xfer-card-count">{{ $nInsumos === 1 ? '1 registro' : $nInsumos . ' registros' }}</span>
                </div>
            </div>
            <div class="index-page__table-wrap table-responsive">
                <table id="insumosAsignadosTable" class="table index-table w-full">
                    <thead>
                        <tr>
                            <th class="xfer-check-col"><input type="checkbox" class="selectAll xfer-check" data-table="insumosAsignadosTable" title="Seleccionar todos"></th>
                            <th>Categoría insumo</th>
                            <th>Nombre insumo</th>
                            <th>Costo mensual</th>
                            <th>Costo anual</th>
                            <th>Frecuencia de pago</th>
                            <th>Observaciones</th>
                            <th>Fecha de asignación</th>
                            <th>Núm. serie</th>
                            <th>Comentarios</th>
                            <th>Mes de pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($insumosAsignados as $insumosAsignado)
                        <tr data-id="{{ $insumosAsignado->InventarioID }}">
                            <td><input type="checkbox" class="selectItem xfer-check" name="insumos[]" value="{{ $insumosAsignado->InventarioID }}"></td>
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
        </div>

        <div class="index-page__card overflow-hidden xfer-card">
            <div class="xfer-card-head">
                <div>
                    <h2>Líneas asignadas</h2>
                    <span class="index-page__count xfer-card-count">{{ $nLineas === 1 ? '1 registro' : $nLineas . ' registros' }}</span>
                </div>
            </div>
            <div class="index-page__table-wrap table-responsive">
                <table id="lineasAsignadosTable" class="table index-table w-full">
                    <thead>
                        <tr>
                            <th class="xfer-check-col"><input type="checkbox" class="selectAll xfer-check" data-table="lineasAsignadosTable" title="Seleccionar todos"></th>
                            <th>Núm. telefónico</th>
                            <th>Compañía</th>
                            <th>Plan tel</th>
                            <th>Costo renta mensual</th>
                            <th>Cuenta padre</th>
                            <th>Cuenta hija</th>
                            <th>Tipo línea</th>
                            <th>Obra</th>
                            <th>Fecha fianza</th>
                            <th>Costo fianza</th>
                            <th>Fecha asignación</th>
                            <th>Estado</th>
                            <th>Comentarios</th>
                            <th>Monto renovación fianza</th>
                            <th>Línea ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($LineasAsignados as $LineasAsignado)
                        <tr data-id="{{ $LineasAsignado->InventarioID }}">
                            <td><input type="checkbox" class="selectItem xfer-check" name="lineas[]" value="{{ $LineasAsignado->InventarioID }}"></td>
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
        </div>

        <div class="crud-page__actions xfer-actions">
            <button type="submit" class="index-page__btn-primary show_confirm">Transferir</button>
            <a href="{{ route('inventarios.index') }}" class="index-page__btn-secondary">Regresar</a>
        </div>
    </form>
</x-index-page>
@endsection

@push('third_party_stylesheets')
    @include('layouts.datatables_css')
    <style>
        .xfer-card { margin-bottom: 1.15rem; }
        .xfer-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.95rem 1rem 0;
        }
        .xfer-card-head h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--index-navy, #101d49);
            letter-spacing: -0.02em;
        }
        .xfer-card .index-page__dt-toolbar { padding-top: 0.5rem; }
        .xfer-check-col { width: 2.5rem; }
        .xfer-check {
            width: 1.05rem;
            height: 1.05rem;
            accent-color: #101d49;
            cursor: pointer;
        }
        .xfer-actions {
            margin-top: 0.25rem;
            padding-top: 0;
            border-top: 0;
        }
        .dark .xfer-card-head h2 { color: #fff; }
    </style>
@endpush

@push('third_party_scripts')
    @include('layouts.datatables_js')
    @include('layouts.partials.index-page-js')
    <script>
        $(document).ready(function () {
            var dtLanguage = {
                sProcessing: 'Procesando...',
                sZeroRecords: 'No se encontraron resultados',
                sEmptyTable: 'Ningún dato disponible en esta tabla',
                sInfo: 'Mostrando _START_ a _END_ de _TOTAL_',
                sInfoEmpty: 'Mostrando 0 a 0 de 0',
                sInfoFiltered: '(filtrado de _MAX_ registros)',
                sSearch: '',
                searchPlaceholder: 'Buscar...',
                oPaginate: {
                    sFirst: 'Primero',
                    sLast: 'Último',
                    sNext: 'Siguiente',
                    sPrevious: 'Anterior'
                }
            };

            function bindTable(selector, emptyLabel) {
                return $(selector).DataTable({
                    destroy: true,
                    responsive: true,
                    paging: true,
                    pageLength: 10,
                    searching: true,
                    ordering: true,
                    info: true,
                    autoWidth: false,
                    columnDefs: [
                        { orderable: false, searchable: false, targets: 0 }
                    ],
                    order: [],
                    dom: "<'index-page__dt-toolbar'f>t<'index-page__dt-footer'ip>",
                    language: Object.assign({}, dtLanguage, {
                        sEmptyTable: emptyLabel
                    }),
                    drawCallback: function () {
                        var api = this.api();
                        var info = api.page.info();
                        var total = info.recordsDisplay;
                        var label = total === 1 ? '1 registro' : total + ' registros';
                        $(api.table().container()).closest('.index-page__card').find('.xfer-card-count').text(label);
                    },
                    initComplete: function () {
                        if (window.IndexPage) {
                            var $input = $(this.api().table().container()).find('.dataTables_filter input');
                            if ($input.length && !$input.attr('placeholder')) {
                                $input.attr('placeholder', 'Buscar...');
                            }
                        }
                    }
                });
            }

            var table = bindTable('#equiposAsignadosTable', 'No hay equipos asignados');
            var table2 = bindTable('#insumosAsignadosTable', 'No hay insumos asignados');
            var table3 = bindTable('#lineasAsignadosTable', 'No hay líneas asignadas');

            $('.selectAll').on('click', function (e) {
                e.stopPropagation();
                var tableId = $(this).data('table');
                var checked = $(this).prop('checked');
                var dataTableInstance = null;
                if (tableId === 'equiposAsignadosTable') dataTableInstance = table;
                else if (tableId === 'insumosAsignadosTable') dataTableInstance = table2;
                else if (tableId === 'lineasAsignadosTable') dataTableInstance = table3;
                if (dataTableInstance) {
                    dataTableInstance.$('input.selectItem').prop('checked', checked);
                }
            });

            $('.show_confirm').on('click', function (event) {
                var form = $(this).closest('form');
                event.preventDefault();

                var totalSeleccionados = 0;
                var tables = [table, table2, table3];
                tables.forEach(function (t) {
                    totalSeleccionados += t.$('input.selectItem:checked').length;
                });

                if (totalSeleccionados === 0) {
                    swal.fire({
                        title: '¡No hay elementos seleccionados!',
                        text: 'Debes seleccionar al menos un equipo, insumo o línea telefónica para realizar la transferencia.',
                        icon: 'error',
                        confirmButtonColor: '#101D49',
                        didOpen: function () {
                            $('.swal2-popup').addClass('dark:bg-[#101010] dark:text-white');
                            $('.swal2-title').addClass('dark:text-white');
                        }
                    });
                    return;
                }

                var empleadosOptions = '';
                @foreach($Empleados as $empleado)
                empleadosOptions += `<option value="{{ $empleado->EmpleadoID }}">{{ $empleado->NombreEmpleado }}</option>`;
                @endforeach

                swal.fire({
                    title: '¿Está seguro de que desea realizar esta acción?',
                    icon: 'warning',
                    html: `
                        <label for="empleado" class="dark:text-white">Selecciona un empleado:</label>
                        <select id="empleado" class="dark:bg-[#101010] dark:text-white">
                            <option value="">--Seleccione un empleado--</option>
                            ${empleadosOptions}
                        </select>
                    `,
                    confirmButtonColor: '#101D49',
                    didOpen: function () {
                        $('#empleado').select2({
                            dropdownParent: $('.swal2-popup'),
                            width: '100%',
                        });
                        $('.swal2-popup').addClass('dark:bg-[#101010]');
                        setTimeout(function () {
                            $('.select2-search__field').addClass('dark:bg-[#101010] dark:text-white dark:placeholder-gray-400');
                            $('.select2-results__option').addClass('dark:bg-[#101010] dark:text-white');
                            $('.select2-dropdown').addClass('dark:bg-[#101010]');
                        }, 100);
                    },
                    showDenyButton: true,
                    confirmButtonText: 'Confirmar',
                    denyButtonText: 'Cerrar',
                    dangerMode: true,
                }).then(function (result) {
                    var selectedEmpleado = $('#empleado').val();
                    if (result.isConfirmed) {
                        if (!selectedEmpleado) {
                            swal.fire({
                                title: '¡Debes seleccionar un empleado!',
                                icon: 'error',
                                confirmButtonColor: '#101D49',
                                didOpen: function () {
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
                                didOpen: function () {
                                    $('.swal2-popup').addClass('dark:bg-[#101010] dark:text-white');
                                    $('.swal2-title').addClass('dark:text-white');
                                }
                            }).then(function () {
                                tables.forEach(function (t) {
                                    t.$('input.selectItem:checked').each(function () {
                                        var name = $(this).attr('name');
                                        form.append('<input type="hidden" name="' + name + '" value="' + this.value + '">');
                                    });
                                });
                                $('input.selectItem').prop('disabled', true);
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
                            didOpen: function () {
                                $('.swal2-popup').addClass('dark:bg-[#101010] dark:text-white');
                                $('.swal2-title').addClass('dark:text-white');
                            }
                        });
                    }
                });
            });
        });
    </script>
    <style>
        .dark .select2-search__field {
            background-color: #101010 !important;
            color: white !important;
        }
        .dark .select2-search__field::placeholder { color: #9ca3af !important; }
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
