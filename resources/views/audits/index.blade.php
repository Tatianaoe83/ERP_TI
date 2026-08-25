@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Informes"
    icon="fa-clipboard"
    subtitle="0 registros"
    :show-count="true"
>
    <x-slot name="filters">
        <div class="form-group">
            <label for="user_type">Responsable del cambio</label>
            {!! Form::select('user_type', App\Models\User::whereIn('id',$usuarios)
            ->pluck('name','id'), null, ['placeholder' => 'Seleccionar...', 'class'=>'jz form-control', 'id' => 'user_type']) !!}
        </div>
        <div class="form-group">
            <label for="auditable_type">Tabla</label>
            {!! Form::select('auditable_type', App\Models\Audit::whereIn('auditable_type',$tablas)
            ->pluck('auditable_type','auditable_type'), null, ['placeholder' => 'Seleccionar...', 'class'=>'jz form-control', 'id' => 'auditable_type']) !!}
        </div>
        <div class="form-group">
            <label for="new_values">Valores</label>
            <input type="text" id="new_values" class="form-control" placeholder="Buscar valores..." autocomplete="off">
        </div>
        @can('buscar-informe')
        <div class="index-page__filters-actions">
            <button id="searchBtn" type="button" class="index-page__btn-primary">Buscar</button>
            <button id="clearInformesBtn" type="button" class="index-page__btn-secondary">Limpiar filtros</button>
        </div>
        @endcan
    </x-slot>

    <div class="index-page__table-wrap table-responsive">
        <table class="table index-table w-full" id="auditsTable">
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
</x-index-page>
@endsection

@push('third_party_stylesheets')
    @include('layouts.datatables_css')
@endpush

@push('third_party_scripts')
@include('layouts.datatables_js')
@include('layouts.partials.index-page-js')

<script>
    window.initInformesTable = function () {
        if (!window.jQuery || !jQuery.fn || typeof jQuery.fn.DataTable !== 'function') {
            return;
        }
        if (!document.getElementById('auditsTable')) {
            return;
        }

        if ($.fn.DataTable.isDataTable('#auditsTable')) {
            $('#auditsTable').DataTable().destroy();
        }

        window.informesTable = $('#auditsTable').DataTable({
            responsive: true,
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
            pageLength: 10,
            dom: "t<'index-page__dt-footer'ip>",
            language: {
                sProcessing: 'Procesando...',
                sLengthMenu: 'Mostrar _MENU_',
                sZeroRecords: 'No se encontraron resultados',
                sEmptyTable: 'Ningún dato disponible en esta tabla',
                sInfo: 'Mostrando _START_ a _END_ de _TOTAL_',
                sInfoEmpty: 'Mostrando 0 a 0 de 0',
                oPaginate: {
                    sFirst: 'Primero',
                    sLast: 'Último',
                    sNext: 'Siguiente',
                    sPrevious: 'Anterior',
                }
            },
            drawCallback: function () {
                if (window.IndexPage) {
                    window.IndexPage.updateCount(this.api());
                }
            },
            initComplete: function () {
                if (window.IndexPage) {
                    window.IndexPage.init(this.api());
                }
            }
        });
    };

    window.recargarInformes = function () {
        if (window.informesTable && typeof window.informesTable.ajax.reload === 'function') {
            window.informesTable.ajax.reload();
        } else {
            window.initInformesTable();
        }
    };

    if (!window.__informesSearchReady) {
        window.__informesSearchReady = true;

        $(document).on('click', '#searchBtn', function (event) {
            event.preventDefault();
            window.recargarInformes();
        });

        $(document).on('click', '#clearInformesBtn', function (event) {
            event.preventDefault();
            window.__informesSkipReload = true;
            $('#new_values').val('');
            $('#user_type').val('').trigger('change');
            $('#auditable_type').val('').trigger('change');
            window.__informesSkipReload = false;
            window.recargarInformes();
        });

        $(document).on('change', '#user_type, #auditable_type', function () {
            if (window.__informesSkipReload) return;
            window.recargarInformes();
        });

        $(document).on('keydown', '#new_values', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                window.recargarInformes();
            }
        });
    }

    window.initInformesTable();
</script>
@endpush
