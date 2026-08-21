@extends('layouts.app')

@section('content')
@include('flash::message')
@include('adminlte-templates::common.errors')

@php
    $nItems = $inventario->count();
    $tareasPreven = [
        1 => 'Desarme y ensamble de equipo',
        2 => 'Formateo e instalación del sistema operativo',
        3 => 'Limpieza interna',
        4 => 'Respaldo de información',
        6 => 'Cambio de pasta térmica',
        7 => 'Limpieza de periféricos',
        8 => 'Actualizaciones de software',
        9 => 'Eliminación de temporales',
        10 => 'Limpieza de ventiladores',
        11 => 'Limpieza de fuente de poder',
        12 => 'Instalación de software por licencia',
        14 => 'Limpieza del teclado',
        15 => 'Cambio de piezas',
        16 => 'Cambio de pasta térmica en la tarjeta gráfica',
        17 => 'Cambio de equipo de cómputo',
    ];
@endphp

<x-index-page
    class="crud-page"
    title="Cartas de entrega"
    icon="fa-print"
    :subtitle="$empleado->NombreEmpleado"
    :card="false"
>
    <x-slot name="headerActions">
        <a href="{{ route('inventarios.index') }}" class="index-page__btn-secondary">Regresar</a>
    </x-slot>

    <p class="text-sm text-gray-500 dark:text-gray-400" style="margin: -0.35rem 0 1.1rem;">
        Genera el formato de mantenimiento preventivo o la carta de entrega de inventario.
    </p>

    <style>
        .cartas-page-stack .cartas-card-head h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--index-navy, #101d49);
            letter-spacing: -0.02em;
        }
        .cartas-page-stack .cartas-card-head--padded {
            padding: 0.95rem 1rem 0;
        }
        .cartas-page-stack .cartas-card .index-page__dt-toolbar { padding-top: 0.5rem; }
        .cartas-page-stack .cartas-check-col { width: 2.5rem; }
        .cartas-page-stack .cartas-check {
            width: 1.05rem;
            height: 1.05rem;
            accent-color: #101d49;
            cursor: pointer;
        }
        .cartas-page-stack .cartas-actions {
            margin: 0;
            padding: 0.85rem 1rem 1rem;
        }
        .dark .cartas-page-stack .cartas-card-head h2 { color: #fff; }
    </style>

    <div class="cartas-page-stack">
    <div class="index-page__card crud-page__card cartas-card">
        <div class="cartas-card-head">
            <div>
                <h2>Mantenimiento preventivo</h2>
                <span class="index-page__count">Selecciona el equipo y las actividades realizadas</span>
            </div>
        </div>

        <form id="formulario2" action="{{ route('inventarios.mantenimiento', $id) }}" method="POST" target="_blank">
            @csrf
            <div class="crud-form">
                {!! Form::label('IdEquipo', 'Equipo') !!}
                {!! Form::select(
                    'IdEquipo',
                    App\Models\InventarioEquipo::select(DB::raw("CONCAT(Folio,' - ', CategoriaEquipo) AS NombreEq, InventarioID"))
                        ->where('EmpleadoID', '=', $id)
                        ->pluck('NombreEq', 'InventarioID'),
                    null,
                    ['placeholder' => 'Seleccionar', 'class' => 'jz form-control', 'style' => 'width: 100%', 'required' => true]
                ) !!}
            </div>

            <div class="crud-select-all" id="selectAllPreven">Seleccionar todos</div>
            <div class="crud-perms">
                @foreach ($tareasPreven as $valor => $etiqueta)
                    <label>
                        <input class="name cursor-pointer" type="checkbox" name="inventarioPreven[]" value="{{ $valor }}" id="defaultCheck{{ $valor }}">
                        <span>{{ $etiqueta }}</span>
                    </label>
                @endforeach
            </div>

            <div class="crud-page__actions">
                <button type="submit" class="index-page__btn-primary">Generar formato</button>
            </div>
        </form>
    </div>

    <form id="formulario" action="{{ route('inventarios.pdffile', $id) }}" method="POST" target="_blank" style="display:block;margin-top:2.5rem !important;">
        @csrf
        <div class="index-page__card overflow-hidden cartas-card">
            <div class="cartas-card-head cartas-card-head--padded">
                <div>
                    <h2>Carta de entrega</h2>
                    <span class="index-page__count cartas-table-count">{{ $nItems === 1 ? '1 registro' : $nItems . ' registros' }}</span>
                </div>
            </div>
            <div class="index-page__table-wrap table-responsive">
                <table class="table index-table w-full" id="inventarioTable">
                    <thead>
                        <tr>
                            <th class="cartas-check-col"><input type="checkbox" id="checkAll" class="cartas-check" title="Seleccionar todos"></th>
                            <th>ID</th>
                            <th>Categoría</th>
                            <th>Marca / nombre</th>
                            <th>Características</th>
                            <th>Modelo</th>
                            <th>Número de serie</th>
                            <th>Fecha asignación / comentarios</th>
                            <th>Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inventario as $item)
                        <tr>
                            <td>
                                <input type="checkbox" class="cartas-check" name="inventarioSeleccionado[]" value="{{ $item->id }}|{{ $item->tipo }}">
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
                                    <span class="index-badge index-badge--dark">Equipo</span>
                                @elseif ($item->tipo == 'INSUMO')
                                    <span class="index-badge index-badge--success">Insumo</span>
                                @elseif ($item->tipo == 'TELEFONO')
                                    <span class="index-badge index-badge--warning">Teléfono</span>
                                @else
                                    {{ $item->tipo }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="crud-page__actions cartas-actions">
                <button type="submit" class="index-page__btn-primary">Generar</button>
                <a href="{{ route('inventarios.index') }}" class="index-page__btn-secondary">Cancelar</a>
            </div>
        </div>
    </form>
    </div>
</x-index-page>
@endsection

@push('third_party_stylesheets')
    @include('layouts.datatables_css')
@endpush

@push('third_party_scripts')
    @include('layouts.datatables_js')
    @include('layouts.partials.index-page-js')
    <script>
        $(document).ready(function () {
            var table = $('#inventarioTable').DataTable({
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
                language: {
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
                },
                drawCallback: function () {
                    var api = this.api();
                    var total = api.page.info().recordsDisplay;
                    var label = total === 1 ? '1 registro' : total + ' registros';
                    $(api.table().container()).closest('.index-page__card').find('.cartas-table-count').text(label);
                },
                initComplete: function () {
                    var $input = $(this.api().table().container()).find('.dataTables_filter input');
                    if ($input.length && !$input.attr('placeholder')) {
                        $input.attr('placeholder', 'Buscar...');
                    }
                }
            });

            $('#checkAll').on('click', function (e) {
                e.stopPropagation();
                table.$("input[name='inventarioSeleccionado[]']").prop('checked', this.checked);
            });
        });

        var selectAllPreven = document.getElementById('selectAllPreven');
        if (selectAllPreven) {
            selectAllPreven.addEventListener('click', function () {
                var checkboxes = document.querySelectorAll('input[name="inventarioPreven[]"]');
                if (!checkboxes.length) return;
                var isChecked = checkboxes[0].checked;
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = !isChecked;
                });
                this.textContent = isChecked ? 'Seleccionar todos' : 'Deseleccionar todos';
            });
        }
    </script>
@endpush
